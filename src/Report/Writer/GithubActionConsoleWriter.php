<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use Generator;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Symfony\Component\Console\Output\OutputInterface;

use function str_replace;

final class GithubActionConsoleWriter
{
    use RenderRangeTrait;

    /**
     * @var Generator<DiagnosticIteratorEntry>
     */
    private Generator $diagnostics;

    public static function writeReport(OutputInterface $output, ReportBuffer $report): void
    {
        $instance = new self($output, $report);
        $instance->write();
    }

    public function __construct(
        private OutputInterface $output,
        private ReportBuffer $report,
        private int $wrapWidth = 80
    ) {
        $this->diagnostics = DiagnosticIterator::filterByMinimumSeverity(
            $this->report,
            TaskReportInterface::SEVERITY_MINOR
        )
            ->thenSortByFileAndRange()
            ->getIterator();
    }

    public function write(): void
    {
        foreach ($this->diagnostics as $diagnostic) {
            $this->writeDiagnostic($diagnostic);
        }
    }

    private function writeDiagnostic(DiagnosticIteratorEntry $entry): void
    {
        $message = $this->compileMessage($entry);
        $range   = $this->compileRange($entry);

        match ($entry->getDiagnostic()->getSeverity()) {
            TaskReportInterface::SEVERITY_MINOR => $this->output->writeln(
                sprintf('::warning %s::%s', $range, $message)
            ),
            default => $this->output->writeln(sprintf('::error %s::%s', $range, $message)),
        };
    }

    private function compileMessage(DiagnosticIteratorEntry $entry): string
    {
        $diagnostic = $entry->getDiagnostic();
        $message    = $this->renderRangePrefix($entry)
            . str_replace("\n", '%0A', $entry->getDiagnostic()->getMessage());

        $reportedBy = 'reported by ' . $entry->getTask()->getTaskName();
        if (null !== ($source = $diagnostic->getSource())) {
            $reportedBy .= ': ' . $source;
        }
        $message .= ' (' . $reportedBy;

        if (null !== ($url = $diagnostic->getExternalInfoUrl())) {
            $message .= ', see ' . $url;
        }
        $message .= ')';

        return $message;
    }

    private function compileRange(DiagnosticIteratorEntry $entry): string
    {
        if (null === ($range = $entry->getRange())) {
            return '';
        }

        $buffer = 'file=' . $range->getFile();
        if (null !== ($line = $range->getStartLine())) {
            $buffer .= ',line=' . (string) $line;
        }
        if (null !== ($column = $range->getStartColumn())) {
            $buffer .= ',col=' . (string) $column;
        }

        return $buffer;
    }

    private function renderRangePrefix(DiagnosticIteratorEntry $entry): string
    {
        if (null === ($range = $entry->getRange()) || null === $range->getEndLine()) {
            return '';
        }

        return $this->renderRange($range) . ' ';
    }
}
