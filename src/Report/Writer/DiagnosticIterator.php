<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use CallbackFilterIterator;
use Generator;
use Iterator;
use IteratorAggregate;
use LogicException;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;

/** @implements IteratorAggregate<int, DiagnosticIteratorEntry>  */
final class DiagnosticIterator implements IteratorAggregate
{
    /**
     * TODO: Use class constants as key when implemented in psalm https://github.com/vimeo/psalm/issues/3555
     * @psalm-var array{
     *  none: int,
     *  info: int,
     *  marginal: int,
     *  minor: int,
     *  major: int,
     *  fatal: int
     * }
     */
    private const SEVERITY_LOOKUP = [
        TaskReportInterface::SEVERITY_NONE     => 0,
        TaskReportInterface::SEVERITY_INFO     => 1,
        TaskReportInterface::SEVERITY_MARGINAL => 2,
        TaskReportInterface::SEVERITY_MINOR    => 3,
        TaskReportInterface::SEVERITY_MAJOR    => 4,
        TaskReportInterface::SEVERITY_FATAL    => 5,
    ];

    /**
     * @var DiagnosticIterator|Generator|Iterator
     * @psalm-var DiagnosticIterator|Generator<int, DiagnosticIteratorEntry>|Iterator
     */
    private $previous;

    /**
     * @var callable|null
     * @psalm-var null|callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int
     */
    private $sortCallback;

    public static function filterByMinimumSeverity(ReportBuffer $report, string $minimumSeverity): DiagnosticIterator
    {
        if (self::SEVERITY_LOOKUP[$minimumSeverity] === 0) {
            return new self(self::reportIterator($report), null);
        }

        return static::createFiltered($report, self::minimumSeverityFilter($minimumSeverity));
    }

    public static function sortByTool(ReportBuffer $report): DiagnosticIterator
    {
        return static::createSorted($report, self::toolSorter());
    }

    public static function sortByFileAndRange(ReportBuffer $report): DiagnosticIterator
    {
        return static::sortBy(static::createSorted($report, self::fileNameSorter()), self::fileRangeSorter());
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
            return $entry1->getTask()->getTaskName() <=> $entry2->getTask()->getTaskName();
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
     * @psalm-return callable(DiagnosticIteratorEntry, mixed, Iterator<mixed, mixed>): bool
     */
    private static function minimumSeverityFilter(string $minimumSeverity): callable
    {
        $threshold = self::SEVERITY_LOOKUP[$minimumSeverity] ?? 0;

        return static function (DiagnosticIteratorEntry $entry) use ($threshold): bool {
            return self::SEVERITY_LOOKUP[$entry->getDiagnostic()->getSeverity()] >= $threshold;
        };
    }

    /**
     * @psalm-param callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int $callback
     */
    private static function createSorted(ReportBuffer $report, callable $callback): DiagnosticIterator
    {
        return new self(self::reportIterator($report), $callback);
    }

    /**
     * @psalm-param callable(DiagnosticIteratorEntry, mixed, Iterator<mixed, mixed>): bool $callback
     */
    private static function createFiltered(ReportBuffer $report, callable $callback): DiagnosticIterator
    {
        return new self(new CallbackFilterIterator(self::reportIterator($report), $callback), null);
    }

    /**
     * @psalm-param callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int $callback
     */
    private static function sortBy(DiagnosticIterator $previous, callable $callback): DiagnosticIterator
    {
        return new self($previous, $callback);
    }

    /**
     * @param DiagnosticIterator|Generator|Iterator $previous
     * @psalm-param DiagnosticIterator|Generator<int, DiagnosticIteratorEntry>|Iterator $previous
     * @psalm-param null|callable(DiagnosticIteratorEntry, DiagnosticIteratorEntry): int $callback
     */
    private function __construct($previous, ?callable $callback)
    {
        $this->previous     = $previous;
        $this->sortCallback = $callback;
    }

    /** @psalm-return Generator<int, DiagnosticIteratorEntry> */
    private static function reportIterator(ReportBuffer $report): Generator
    {
        foreach ($report->getTaskReports() as $taskReport) {
            foreach ($taskReport->getDiagnostics() as $diagnostic) {
                if ($diagnostic->hasFileRanges()) {
                    foreach ($diagnostic->getFileRanges() as $range) {
                        yield new DiagnosticIteratorEntry($taskReport, $diagnostic, $range);
                    }
                    continue;
                }
                yield new DiagnosticIteratorEntry($taskReport, $diagnostic, null);
            }
        }
    }

    /** @@SuppressWarnings(PHPMD.UnusedPrivateMethod) - not unused, it is used in getIterator() and iterateSorted(). */
    private function compare(DiagnosticIteratorEntry $left, DiagnosticIteratorEntry $right): int
    {
        // If parent is sorting, then test if it already determines the result order.
        if ($this->previous instanceof DiagnosticIterator && null !== $this->previous->sortCallback) {
            if (0 !== $result = $this->previous->compare($left, $right)) {
                return $result;
            }
        }

        /** @psalm-suppress PossiblyNullFunctionCall - getIterator() checks if methods exist. Invoking compare form
         * other place is not allowed.
         */
        return call_user_func($this->sortCallback, $left, $right);
    }

    /**
     * @return Generator|DiagnosticIteratorEntry[]
     * @psalm-return Generator<int, DiagnosticIteratorEntry>
     */
    public function getIterator(): Generator
    {
        if ($this->previous instanceof self && $this->previous->sortCallback) {
            foreach ($this->iterateSorted() as $entry) {
                yield $entry;
            }
            return;
        }

        if (null === $this->sortCallback) {
            foreach ($this->previous as $entry) {
                yield $entry;
            }
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
     * @psalm-return Generator<int, DiagnosticIteratorEntry, mixed, null>
     */
    private function iterateSorted(): Generator
    {
        assert($this->previous instanceof self);
        $parent = $this->previous->getIterator();
        // Empty list - no need to sort.
        if (!$parent->valid()) {
            return;
        }
        /** @var DiagnosticIteratorEntry $entry1 */
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
            // Partition boundary reached - sort elements, emit them and execute over.
            /** @psalm-suppress PossiblyNullArgument - iterateSorted is only called then sortCallback exists */
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
        /** @psalm-suppress PossiblyNullArgument - iterateSorted is only called then sortCallback exists */
        usort($partition, $this->sortCallback);
        foreach ($partition as $item) {
            yield $item;
        }
    }
}
