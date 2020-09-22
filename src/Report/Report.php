<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\ToolReportInterface;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Repository\RepositoryInterface;

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
        // FIXME: Rework tool reports to task reports
        return new ToolReport($this->report->createToolReport($toolName, 'unknown'), $this->tempDir);
    }
}
