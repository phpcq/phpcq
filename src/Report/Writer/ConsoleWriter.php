<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use Generator;
use Phpcq\Report\Buffer\FileRangeBuffer;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\ToolReport;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

use function explode;
use function str_repeat;
use function strip_tags;
use function strlen;
use function wordwrap;

final class ConsoleWriter
{
    /** @var OutputInterface */
    private $output;

    /** @var int */
    private $wrapWidth;

    /** @var ReportBuffer */
    private $report;

    /**
     * @var Generator|DiagnosticIteratorEntry[]
     * @psalm-var Generator<int, DiagnosticIteratorEntry>
     */
    private $diagnostics;

    public static function writeReport(OutputInterface $output, ReportBuffer $report): void
    {
        $instance = new self($output, $report);
        $instance->write();
    }

    public function __construct(OutputInterface $output, ReportBuffer $report)
    {
        $this->output      = $output;
        $this->report      = $report;
        $this->diagnostics = DiagnosticIterator::sortByTool($this->report)->thenSortByFileAndRange()->getIterator();

        $this->wrapWidth = 80;
        if ($output instanceof ConsoleOutputInterface) {
            $terminal = new Terminal();
            $this->wrapWidth = $terminal->getWidth();
        }
    }

    public function write(): void
    {
        $this->writeHeadline();
        $this->writeToolOverview();

        if (!$this->output->isVerbose()) {
            return;
        }

        while ($this->diagnostics->valid()) {
            $this->writeToolReport();
        }
    }

    private function writeHeadline(): void
    {
        $this->output->writeln('');
        $this->output->writeln(sprintf('PHP Code Quality check finished with state "%s"', $this->report->getStatus()));
        $this->output->writeln('');
    }

    private function writeToolOverview(): void
    {
        $this->writeSectionHeadline('Tool overview');

        $toolOverviewTable = new Table($this->output);
        $toolOverviewTable->setHeaders(['Tool', 'State']);
        foreach ($this->report->getToolReports() as $toolReport) {
            $toolOverviewTable->addRow([$toolReport->getToolName(), $this->renderToolStatus($toolReport->getStatus())]);
        }
        $toolOverviewTable->render();

        $this->output->writeln('');
    }

    private function writeToolReport(): void
    {
        /** @var DiagnosticIteratorEntry $entry */
        $entry = $this->diagnostics->current();
        $report = $entry->getTool();
        $this->writeSectionHeadline(
            sprintf('Tool report "%s": %s', $report->getToolName(), $this->renderToolStatus($report->getStatus()))
        );

        do {
            $this->writeFileReport();
        } while ($this->diagnostics->valid() && $report === $this->diagnostics->current()->getTool());
    }

    private function writeFileReport(): void
    {
        /** @var DiagnosticIteratorEntry $entry */
        $entry = $this->diagnostics->current();

        $fileName = null;
        if ($range = $entry->getRange()) {
            $fileName = $range->getFile();
            $this->writeSectionHeadline('File <href= ' . $fileName . '>' . $fileName . '</>');
        } else {
            $this->writeSectionHeadline('Generic diagnostics');
        }

        do {
            $diagnostic = $entry->getDiagnostic();
            $severity  = $this->renderDiagnosticSeverity($diagnostic->getSeverity());
            $severity .= str_repeat(' ', 8 - strlen($diagnostic->getSeverity()));
            $ident     = str_repeat(' ', 10); // Warning is the longest severity

            $this->output->write($severity);
            $this->renderMultiline($diagnostic->getMessage(), $ident);

            $range  = $entry->getRange();
            $source = $diagnostic->getSource();
            if ($source || null !== $range) {
                $this->output->write($ident);
                if ($source) {
                    $this->output->write('Source: ' . $source . ' ');
                }
                if ($range) {
                    $this->output->write($this->renderRange($range));
                }
                $this->output->writeln('');
            }

            $this->output->writeln('');

            $this->diagnostics->next();
            if (!$this->diagnostics->valid()) {
                break;
            }
            $entry = $this->diagnostics->current();
        } while ($entry->getFileName() === $fileName);

        $this->output->writeln('');
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

    private function writeSectionHeadline(string $headline): void
    {
        $this->output->writeln($headline);
        $this->output->writeln(str_repeat('-', strlen(strip_tags($headline))));
        $this->output->writeln('');
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

    private function renderDiagnosticSeverity(string $severity): string
    {
        switch ($severity) {
            case ToolReport::SEVERITY_INFO:
                return '<fg=white>[' . $severity . ']</>';

            case ToolReport::SEVERITY_WARNING:
                return '<fg=yellow>[' . $severity . ']</>';

            case ToolReport::SEVERITY_ERROR:
                return '<fg=red>[' . $severity . ']</>';
        }

        return '[' . $severity . ']';
    }


    private function renderMultiline(string $message, string $prefix): void
    {
        $lines = explode("\n", $message);
        if (count($lines) === 1) {
            $lines = explode("\n", wordwrap($lines[0], $this->wrapWidth));
        }

        $this->output->writeln($lines[0]);

        for ($line = 1; $line < count($lines); $line++) {
            $this->output->writeln($prefix . $lines[$line]);
        }
    }
}
