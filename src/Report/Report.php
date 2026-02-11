<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report;

use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;

final class Report implements ReportInterface
{
    public function __construct(private readonly ReportBuffer $report, private readonly string $tempDir)
    {
    }

    #[\Override]
    public function addTaskReport(string $taskName, array $metadata = []): TaskReportInterface
    {
        return new TaskReport($this->report->createTaskReport($taskName, $metadata), $this->tempDir);
    }
}
