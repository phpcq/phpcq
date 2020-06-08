<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\PluginApi\Version10\ReportInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\ReportBuffer;

final class Report implements ReportInterface
{
    /**
     * @var ReportBuffer
     */
    private $report;

    /** @var string */
    private $tempDir;

    public function __construct(ReportBuffer $report, string $tempDir)
    {
        $this->report  = $report;
        $this->tempDir = $tempDir;
    }

    public function addToolReport(string $toolName): ToolReportInterface
    {
        return new ToolReport($this->report->createToolReport($toolName), $this->tempDir);
    }
}
