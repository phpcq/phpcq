<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
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

    public function addTaskReport(string $taskName, array $metadata = []): TaskReportInterface
    {
        return new TaskReport($this->report->createTaskReport($taskName, $metadata), $this->tempDir);
    }
}
