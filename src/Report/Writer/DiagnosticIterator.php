<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use Generator;
use IteratorAggregate;
use LogicException;
use Phpcq\Report\Buffer\ReportBuffer;

final class DiagnosticIterator implements IteratorAggregate
{
    /**
     * @var DiagnosticIterator|Generator
     * @psalm-var DiagnosticIterator|Generator<int, DiagnosticIteratorEntry>
     */
    private $previous;

    /**
     * @var callable
     * @psalm-var callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int
     */
    private $sortCallback;

    public static function sortByTool(ReportBuffer $report): DiagnosticIterator
    {
        return static::create($report, self::toolSorter());
    }

    public static function sortByFileAndRange(ReportBuffer $report): DiagnosticIterator
    {
        return static::sortBy(static::create($report, self::fileNameSorter()), self::fileRangeSorter());
    }

    public function thenSortByTool(): self
    {
        return static::sortBy($this, self::toolSorter());
    }

    public function thenSortByFileAndRange(): self
    {
        return static::sortBy(static::sortBy($this, self::fileNameSorter()), self::fileRangeSorter());
    }

    /** @psalm-return callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int */
    private static function toolSorter(): callable
    {
        return static function (DiagnosticIteratorEntry $entry1, DiagnosticIteratorEntry $entry2): int {
            return $entry1->getTool()->getToolName() <=> $entry2->getTool()->getToolName();
        };
    }

    /** @psalm-return callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int */
    private static function fileNameSorter(): callable
    {
        return static function (DiagnosticIteratorEntry $entry1, DiagnosticIteratorEntry $entry2): int {
            $range1 = $entry1->getRange();
            $range2 = $entry2->getRange();
            if (null === $range1) {
                if (null === $range2) {
                    return 0;
                }
                return -1;
            }
            if (null === $range2) {
                return 1;
            }

            return $range1->getFile() <=> $range2->getFile();
        };
    }

    /** @psalm-return callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int */
    private static function fileRangeSorter(): callable
    {
        return static function (DiagnosticIteratorEntry $entry1, DiagnosticIteratorEntry $entry2): int {
            $range1 = $entry1->getRange();
            $range2 = $entry2->getRange();
            if (null === $range1) {
                if (null === $range2) {
                    return 0;
                }
                return -1;
            }
            if (null === $range2) {
                return 1;
            }

            if (($value1 = (int) $range1->getStartLine()) !== ($value2 = (int) $range2->getStartLine())) {
                return $value1 <=> $value2;
            }
            if (($value1 = (int) $range1->getStartColumn()) !== ($value2 = (int) $range2->getStartColumn())) {
                return $value1 <=> $value2;
            }
            if (($value1 = (int) $range1->getEndLine()) !== ($value2 = (int) $range2->getEndLine())) {
                return $value1 <=> $value2;
            }

            return (int) $range1->getEndColumn() <=> (int) $range2->getEndColumn();
        };
    }

    /**
     * @psalm-param callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int $callback
     */
    private static function create(ReportBuffer $report, callable $callback): DiagnosticIterator
    {
        return new self(self::reportIterator($report), $callback);
    }

    /**
     * @psalm-param callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int $callback
     */
    private static function sortBy(DiagnosticIterator $previous, callable $callback): DiagnosticIterator
    {
        return new self($previous, $callback);
    }

    /**
     * @param DiagnosticIterator|Generator $previous
     * @psalm-param DiagnosticIterator|Generator<int, DiagnosticIteratorEntry> $previous
     * @psalm-param callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int $callback
     */
    private function __construct($previous, callable $callback)
    {
        $this->previous     = $previous;
        $this->sortCallback = $callback;
    }

    /** @psalm-return Generator<int, DiagnosticIteratorEntry> */
    private static function reportIterator(ReportBuffer $report): Generator
    {
        foreach ($report->getToolReports() as $toolReport) {
            foreach ($toolReport->getDiagnostics() as $diagnostic) {
                if ($diagnostic->hasFileRanges()) {
                    foreach ($diagnostic->getFileRanges() as $range) {
                        yield new DiagnosticIteratorEntry($toolReport, $diagnostic, $range);
                    }
                    continue;
                }
                yield new DiagnosticIteratorEntry($toolReport, $diagnostic, null);
            }
        }
    }

    /** @@SuppressWarnings(PHPMD.UnusedPrivateMethod) - not unused, it is used in getIterator() and iterateSorted(). */
    private function compare(DiagnosticIteratorEntry $left, DiagnosticIteratorEntry $right): int
    {
        // If parent is sorting, then test if it already determines the result order.
        if ($this->previous instanceof DiagnosticIterator) {
            if (0 !== $result = $this->previous->compare($left, $right)) {
                return $result;
            }
        }

        return call_user_func($this->sortCallback, $left, $right);
    }

    /**
     * @return Generator|DiagnosticIteratorEntry[]
     * @psalm-return Generator<int, DiagnosticIteratorEntry>
     */
    public function getIterator(): Generator
    {
        if ($this->previous instanceof self) {
            yield from $this->iterateSorted();
            return;
        }

        $values = iterator_to_array($this->previous);
        usort($values, [$this, 'compare']);

        foreach ($values as $entry) {
            yield $entry;
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @psalm-return Generator<int, DiagnosticIteratorEntry, mixed, void>
     */
    private function iterateSorted(): Generator
    {
        assert($this->previous instanceof self);
        $parent = $this->previous->getIterator();
        // Empty list - no need to sort.
        if (!$parent->valid()) {
            return;
        }
        $entry1 = $parent->current();
        $parent->next();
        // Single item - no need to sort.
        if (!$parent->valid()) {
            yield $entry1;
            return;
        }
        $partition = [];
        while (true) {
            // Per definition: The previous provider sorts all items by its condition.
            // Buffer all elements until the next "partition" begins.
            $entry2 = $parent->current();
            $delta  = $this->previous->compare($entry1, $entry2);
            if ($delta > 0) {
                throw new LogicException('Parent appears to be unsorted?!?');
            }
            if (0 === $delta) {
                $partition[] = $entry1;
                $entry1      = $entry2;
                $parent->next();
                if (!$parent->valid()) {
                    break;
                }
                continue;
            }
            $partition[] = $entry1;
            // Partition boundary reached - sort elements, emit them and start over.
            usort($partition, $this->sortCallback);
            foreach ($partition as $item) {
                yield $item;
            }
            // entry2 is already from the next partition.
            $partition = [];
            $entry1    = $entry2;
            $parent->next();
            if (!$parent->valid()) {
                break;
            }
        }
        $partition[] = $entry1;
        // End of list reached - sort elements and emit them.
        usort($partition, $this->sortCallback);
        foreach ($partition as $item) {
            yield $item;
        }
    }
}
