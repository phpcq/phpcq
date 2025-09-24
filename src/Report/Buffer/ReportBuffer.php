<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Buffer;

use DateTimeImmutable;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;

use function array_values;

/**
 * TODO: Use class constants as key when implemented in psalm https://github.com/vimeo/psalm/issues/3555
 * @psalm-type TReportSummary = array{
 *  none: int,
 *  info: int,
 *  marginal: int,
 *  minor: int,
 *  major: int,
 *  fatal: int
 * }
 */
final class ReportBuffer
{
    private string $status = 'started';

    private readonly DateTimeImmutable $startedAt;

    private ?DateTimeImmutable $completedAt = null;

    /**
     * @var array<string,TaskReportBuffer>
     */
    private array $taskReports = [];

    public function __construct()
    {
        $this->startedAt = new DateTimeImmutable();
    }

    /** @param array<string,string> $metadata */
    public function createTaskReport(string $taskName, array $metadata = []): TaskReportBuffer
    {
        $reportName = $taskName;
        if (isset($this->taskReports[$reportName])) {
            $number = 0;
            do {
                $reportName = $taskName . '-' . ((string) ++$number);
            } while (isset($this->taskReports[$reportName]));
        }
        return $this->taskReports[$reportName] = new TaskReportBuffer($taskName, $reportName, $metadata);
    }

    public function complete(string $status): void
    {
        $this->status      = $status;
        $this->completedAt = new DateTimeImmutable();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    /**
     * @return list<TaskReportBuffer>
     */
    public function getTaskReports(): iterable
    {
        return array_values($this->taskReports);
    }

    /**
     * @return TReportSummary
     */
    public function countDiagnosticsGroupedBySeverity(): array
    {
        /** @var TReportSummary $summary */
        $summary = [
            TaskReportInterface::SEVERITY_FATAL    => 0,
            TaskReportInterface::SEVERITY_MAJOR    => 0,
            TaskReportInterface::SEVERITY_MINOR    => 0,
            TaskReportInterface::SEVERITY_MARGINAL => 0,
            TaskReportInterface::SEVERITY_INFO     => 0,
            TaskReportInterface::SEVERITY_NONE     => 0,
        ];

        foreach ($this->getTaskReports() as $taskReport) {
            foreach ($taskReport->countDiagnosticsGroupedBySeverity() as $severity => $count) {
                $summary[$severity] += $count;
            }
        }

        return $summary;
    }
}
