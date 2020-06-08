<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use Generator;
use Phpcq\PluginApi\Version10\ReportInterface;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\ToolReport;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

use function explode;
use function str_repeat;
use function wordwrap;

final class ConsoleWriter
{
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
        $this->style->title('PHP Code Quality check');
    }

    private function writeSummary(): void
    {
        $rows = [];
        foreach ($this->report->getToolReports() as $toolReport) {
            $rows[] = [$toolReport->getToolName(), $this->renderToolStatus($toolReport->getStatus())];
        }

        $this->style->table(['Tool', 'State'], $rows);
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

        $source = $entry->getTool()->getToolName();
        if (null !== ($diagnosticSource = $diagnostic->getSource())) {
            $source .= ' (' . $diagnosticSource . ')';
        }

        $this->output->write(sprintf(' <fg=white>reported by %s:</>', $source));

        $this->style->newLine();
    }

    private function writeConclusion(): void
    {
        $this->output->writeln(
            sprintf('Code quality check %s.', $this->renderReportStatus($this->report->getStatus()))
        );
        $this->style->newLine();
    }

    private function renderRange(FileRangeBuffer $range): string
    {
        if (null === $value = $range->getStartLine()) {
            return '';
        }
        $result = '[' . (string) $value;
        if (null !== $value = $range->getStartColumn()) {
            $result .= ':' . (string) $value;
        }
        if (null !== $value = $range->getEndLine()) {
            $result .= ' - ' . (string) $value;
            if (null !== $value = $range->getEndColumn()) {
                $result .= ':' . (string) $value;
            }
        }

        return $result . ']';
    }

    private function renderToolStatus(string $status): string
    {
        switch ($status) {
            case ToolReport::STATUS_STARTED:
                return '<fg=yellow>' . $status . '</>';

            case ToolReport::STATUS_PASSED:
                return '<fg=green>' . $status . '</>';

            case ToolReport::STATUS_FAILED:
                return '<fg=red>' . $status . '</>';
        }

        return $status;
    }

    private function renderReportStatus(string $status): string
    {
        switch ($status) {
            case ReportInterface::STATUS_STARTED:
                return '<fg=yellow>' . $status . '</>';

            case ReportInterface::STATUS_PASSED:
                return '<fg=green>' . $status . '</>';

            case ReportInterface::STATUS_FAILED:
                return '<fg=red>' . $status . '</>';
        }

        return $status;
    }

    private function renderDiagnosticSeverity(string $severity, ?string $message = null): string
    {
        $message = $message ?: $severity;

        switch ($severity) {
            case ToolReport::SEVERITY_INFO:
                return '<fg=white>' . $message . '</>';

            case ToolReport::SEVERITY_WARNING:
                return '<fg=yellow>' . $message . '</>';

            case ToolReport::SEVERITY_ERROR:
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
