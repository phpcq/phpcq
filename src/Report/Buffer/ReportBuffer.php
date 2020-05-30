<?php

declare(strict_types=1);

namespace Phpcq\Report\Buffer;

use DateTimeImmutable;

use function array_values;

final class ReportBuffer
{
    /** @var string */
    private $status = 'started';

    /** @var DateTimeImmutable */
    private $startedAt;

    /** @var DateTimeImmutable|null */
    private $completedAt;

    /**
     * @psalm-var array<string,ToolReportBuffer>
     * @var ToolReportBuffer[]
     */
    private $toolReports = [];

    public function __construct()
    {
        $this->startedAt = new DateTimeImmutable();
    }

    public function createToolReport(string $toolName): ToolReportBuffer
    {
        $reportName = $toolName;
        if (isset($this->toolReports[$reportName])) {
            $number = 0;
            do {
                $reportName = $toolName . '-' . ++$number;
            } while (isset($this->toolReports[$reportName]));
        }
        // FIXME: do we rather want to keep the tool name and add the report name as second argument?
        return $this->toolReports[$reportName] = new ToolReportBuffer($reportName);
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
     * @return ToolReportBuffer[]|iterable
     *
     * @psalm-return list<ToolReportBuffer>
     */
    public function getToolReports(): iterable
    {
        return array_values($this->toolReports);
    }
}