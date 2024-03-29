<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use DateTimeImmutable;
use Generator;
use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\TaskReport;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

use function array_filter;
use function explode;
use function sprintf;
use function str_repeat;
use function wordwrap;

final class ConsoleWriter
{
    use RenderRangeTrait;

    /**
     * @var OutputInterface
     */
    private $output;

    /** @var StyleInterface */
    private $style;

    /** @var int */
    private $wrapWidth;

    /** @var ReportBuffer */
    private $report;

    /**
     * @var Generator|DiagnosticIteratorEntry[]
     * @psalm-var Generator<int, DiagnosticIteratorEntry>
     */
    private $diagnostics;

    public static function writeReport(
        OutputInterface $output,
        StyleInterface $style,
        ReportBuffer $report,
        string $minimumSeverity,
        int $wrapWidth = 80
    ): void {
        $instance = new self($output, $style, $report, $minimumSeverity, $wrapWidth);
        $instance->write();
    }

    public function __construct(
        OutputInterface $output,
        StyleInterface $style,
        ReportBuffer $report,
        string $minimumSeverity,
        int $wrapWidth = 80
    ) {
        $this->output      = $output;
        $this->style       = $style;
        $this->report      = $report;
        $this->wrapWidth   = $wrapWidth;
        $this->diagnostics = DiagnosticIterator::filterByMinimumSeverity($report, $minimumSeverity)
            ->thenSortByFileAndRange()
            ->getIterator();
    }

    public function write(): void
    {
        $this->writeHeadline();
        $this->writeSummary();

        if (!$this->output->isVerbose()) {
            $this->writeConclusion();
            return;
        }

        $file = null;

        while ($this->diagnostics->valid()) {
            /** @var DiagnosticIteratorEntry $entry */
            $entry = $this->diagnostics->current();

            if ($file !== $entry->getFileName() && (null !== ($file = $entry->getFileName()))) {
                $this->style->newLine();
                $this->style->section($file);
            }

            $this->writeEntryReport($entry);
            $this->diagnostics->next();
        }

        $this->writeConclusion();
    }

    private function writeHeadline(): void
    {
        $this->style->title('PHP Code Quality Report');
    }

    private function writeSummary(): void
    {
        $rows = [];
        foreach ($this->report->getTaskReports() as $taskReport) {
            $metadata = $taskReport->getMetadata();
            $rows[] = [
                $taskReport->getTaskName(),
                ($taskReport->getMetadata()['tool_name'] ?? ''),
                ($metadata['tool_version'] ?? ''),
                $this->renderToolStatus($taskReport->getStatus()),
            ];
        }

        $this->style->table(['Task', 'Tool', 'Version', 'State'], $rows);
    }

    private function writeEntryReport(DiagnosticIteratorEntry $entry): void
    {
        $diagnostic = $entry->getDiagnostic();

        $this->writeEntrySummary($entry);
        $this->renderMultiline($diagnostic->getMessage(), 4);
        $this->style->newLine();
    }

    private function writeEntrySummary(DiagnosticIteratorEntry $entry): void
    {
        $diagnostic = $entry->getDiagnostic();
        $range = $entry->getRange();
        if (null === $range) {
            $this->output->write($this->renderDiagnosticSeverity(
                $diagnostic->getSeverity(),
                strtoupper($diagnostic->getSeverity())
            ));
        } else {
            $this->output->write($this->renderDiagnosticSeverity(
                $diagnostic->getSeverity(),
                trim($this->renderRange($range) . ' ' . strtoupper($diagnostic->getSeverity()) . ' ')
            ));
        }

        $source = $entry->getTask()->getTaskName();
        if (null !== ($diagnosticSource = $diagnostic->getSource())) {
            $source .= ' (' . $diagnosticSource . ')';
        }

        $this->output->write(sprintf(' <fg=white>reported by %s:</>', $source));

        $this->style->newLine();
    }

    private function writeConclusion(): void
    {
        $conclusion = $this->renderReportStatus($this->report->getStatus());
        $summary = array_filter($this->report->countDiagnosticsGroupedBySeverity());
        if (count($summary) > 0) {
            $conclusion .= ': ';
            $prefix = '';
            foreach ($summary as $severity => $count) {
                $conclusion .= $prefix . $count . ' ' . $severity . ($count > 1 ? 's' : '');
                $prefix = ', ';
            }
        }
        $conclusion .= '.';
        $this->output->writeln($conclusion);

        $completedAt = $this->report->getCompletedAt();
        assert($completedAt instanceof DateTimeImmutable);
        $runningTime = $completedAt->diff($this->report->getStartedAt());
        $this->output->writeln(sprintf('<fg=green>Elapsed time:</> %s', $runningTime->format('%hh %mm %ss.')));

        $this->style->newLine();
    }

    private function renderToolStatus(string $status): string
    {
        switch ($status) {
            case TaskReport::STATUS_STARTED:
                return '<fg=yellow>' . $status . '</>';

            case TaskReport::STATUS_PASSED:
                return '<fg=green>' . $status . '</>';

            case TaskReport::STATUS_FAILED:
                return '<fg=red>' . $status . '</>';
        }

        return $status;
    }

    private function renderReportStatus(string $status): string
    {
        switch ($status) {
            case ReportInterface::STATUS_STARTED:
                $color = 'yellow';
                break;

            case ReportInterface::STATUS_PASSED:
                $color = 'green';
                break;

            case ReportInterface::STATUS_FAILED:
                $color = 'red';
                break;

            default:
                $color = 'white';
        }

        return sprintf('<fg=%s>Code quality check %s</>', $color, $status);
    }

    private function renderDiagnosticSeverity(string $severity, ?string $message = null): string
    {
        $message = $message ?: $severity;

        switch ($severity) {
            case TaskReport::SEVERITY_NONE:
            case TaskReport::SEVERITY_INFO:
                return '<fg=white>' . $message . '</>';

            case TaskReport::SEVERITY_MARGINAL:
            case TaskReport::SEVERITY_MINOR:
                return '<fg=yellow>' . $message . '</>';

            case TaskReport::SEVERITY_MAJOR:
            case TaskReport::SEVERITY_FATAL:
                return '<fg=red>' . $message . '</>';
        }

        return $message;
    }

    private function renderMultiline(string $message, int $indent): void
    {
        $prefix = str_repeat(' ', $indent);
        $lines  = explode("\n", $message);

        if (count($lines) === 1) {
            $this->output->writeln($prefix . $lines[0]);
            return;
        }

        foreach ($lines as $line) {
            $wrapped = explode("\n", wordwrap($line, $this->wrapWidth));
            foreach ($wrapped as $wrappedLine) {
                $this->renderMultiline($wrappedLine, $indent);
            }
        }
    }
}
