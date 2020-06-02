<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Buffer\SourceFileBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use Phpcq\Report\ToolReport;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

use function explode;
use function str_repeat;
use function strip_tags;
use function strlen;
use function wordwrap;

final class ConsoleWriter
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var ReportBuffer
     */
    private $report;

    public static function writeReport(OutputInterface $output, ReportBuffer $report) : void
    {
        $instance = new self($output, $report);
        $instance->write();
    }

    public function __construct(OutputInterface $output, ReportBuffer $report)
    {
        $this->output = $output;
        $this->report = $report;
    }

    public function write() : void
    {
        $this->writeHeadline();
        $this->writeToolOverview();

        foreach ($this->report->getToolReports() as $toolReport) {
            $this->writeToolReport($toolReport);
        }
    }

    private function writeHeadline() : void
    {
        $this->output->writeln('');
        $this->output->writeln(sprintf('PHP Code Quality check finished with state "%s"', $this->report->getStatus()));
        $this->output->writeln('');
    }

    private function writeToolOverview() : void
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

    private function writeToolReport(ToolReportBuffer $report) : void
    {
        $this->writeSectionHeadline(
            sprintf('Tool report "%s": %s', $report->getToolName(), $this->renderToolStatus($report->getStatus()))
        );

        foreach ($report->getFiles() as $file) {
            $this->writeFileReport($file);
        }
    }

    private function writeFileReport(SourceFileBuffer $file) : void
    {
        if ($file->count() === 0) {
            return;
        }

        $this->writeSectionHeadline('File <href= ' . $file->getFilePath() . '>' . $file->getFilePath() . '</>', '-');

        foreach ($file as $error) {
            $severity  = $this->renderErrorSeverity($error->getSeverity());
            $severity .= str_repeat(' ', 8 - strlen($error->getSeverity()));
            $ident     = str_repeat(' ', 10); // Warning is the longest severity

            $this->output->write($severity);
            $this->renderMultiline($error->getMessage(), $ident);

            if ($error->getSource() || $error->getLine()) {
                $this->output->write($ident);
            }

            if ($error->getSource()) {
                $this->output->write('Source: ' . $error->getSource() . ' ');
            }

            if ($error->getLine()) {
                $this->output->write('[');
                $this->output->write($error->getLine());

                if ($error->getColumn() !== null) {
                    $this->output->write(':' . $error->getColumn());
                }
                $this->output->write(']');
            }

            if ($error->getSource() || $error->getLine()) {
                $this->output->writeln('');
            }

            $this->output->writeln('');
        }

        $this->output->writeln('');
    }

    private function writeSectionHeadline(string $headline, $character = '=') : void
    {
        $this->output->writeln($headline);
        $this->output->writeln(str_repeat('-', strlen(strip_tags($headline))));
        $this->output->writeln('');
    }

    private function renderToolStatus(string $status) : string
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

    private function renderErrorSeverity(string $severity) : string
    {
        switch ($severity) {
            case ToolReport::SEVERITY_INFO:
                return '<fg=yellow>[' . $severity . ']</>';

            case ToolReport::SEVERITY_WARNING:
                return '<fg=orange>[' . $severity . ']</>';

            case ToolReport::SEVERITY_ERROR:
                return '<fg=red>[' . $severity . ']</>';
        }

        return '[' . $severity . ']';
    }


    private function renderMultiline(string $message, string $prefix) : void
    {
        $lines = explode("\n", $message);
        if (count($lines) === 1) {
            $lines = explode("\n", wordwrap($lines[0]));
        }

        if ($lines === false) {
            return;
        }

        $this->output->writeln($lines[0]);

        for ($line = 1; $line < count($lines); $line++) {
            $this->output->writeln($prefix . $lines[$line]);
        }
    }
}
