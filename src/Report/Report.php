<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report;

use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;

final class Report implements ReportInterface
{
    public function __construct(
        private readonly ReportBuffer $report,
        private readonly string $tempDir,
        private readonly TaskKind $kind = TaskKind::Diagnostic
    ) {
    }

    /**
     * Create a report writing to the same buffer which marks its task reports with the given kind.
     */
    public function withKind(TaskKind $kind): self
    {
        return new self($this->report, $this->tempDir, $kind);
    }

    #[\Override]
    public function addTaskReport(string $taskName, array $metadata = []): TaskReportInterface
    {
        return new TaskReport($this->report->createTaskReport($taskName, $metadata, $this->kind), $this->tempDir);
    }
}
