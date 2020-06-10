<?php

declare(strict_types=1);

namespace Phpcq\Report\Buffer;

use DateTimeImmutable;
use Phpcq\PluginApi\Version10\ToolReportInterface;

use function array_values;

/**
 * TODO: Use class constants as key when implemented in psalm https://github.com/vimeo/psalm/issues/3555
 * @psalm-type TReportSummary = array{
 *  info: int,
 *  notice: int,
 *  warning: int,
 *  error: int
 * }
 */
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
        return $this->toolReports[$reportName] = new ToolReportBuffer($toolName, $reportName);
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

    /**
     * @psalm-return TReportSummary
     */
    public function countDiagnosticsGroupedBySeverity(): array
    {
        $summary = [
            ToolReportInterface::SEVERITY_ERROR   => 0,
            ToolReportInterface::SEVERITY_WARNING => 0,
            ToolReportInterface::SEVERITY_NOTICE  => 0,
            ToolReportInterface::SEVERITY_INFO    => 0,
        ];

        foreach ($this->getToolReports() as $toolReport) {
            foreach ($toolReport->countDiagnosticsGroupedBySeverity() as $severity => $count) {
                $summary[$severity] += $count;
            }
        }

        return $summary;
    }
}
