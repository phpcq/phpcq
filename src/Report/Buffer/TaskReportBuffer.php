<?php

declare(strict_types=1);

namespace Phpcq\Report\Buffer;

use Generator;
use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;

/**
 * TODO: Use class constants as key when implemented in psalm https://github.com/vimeo/psalm/issues/3555
 * @psalm-type TTaskReportSummary = array{
 *  none: int,
 *  info: int,
 *  marginal: int,
 *  minor: int,
 *  major: int,
 *  fatal: int
 * }
 */
final class TaskReportBuffer
{
    /** @var string */
    private $taskName;

    /** @var string */
    private $status;

    /** @var DiagnosticBuffer[] */
    private $diagnostics = [];

    /** @var AttachmentBuffer[] */
    private $attachments = [];

    /** @var DiffBuffer[] */
    private $diffs = [];

    /** @var string */
    private $reportName;

    /** @var array<string,string> */
    private $metadata;

    /** @psam-param array<string,string> $metadata */
    public function __construct(string $taskName, string $reportName, array $metadata = [])
    {
        $this->taskName   = $taskName;
        $this->reportName = $reportName;
        $this->metadata   = $metadata;
        $this->status     = ReportInterface::STATUS_STARTED;
    }

    /**
     * Get toolName.
     *
     * @return string
     */
    public function getTaskName(): string
    {
        return $this->taskName;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getReportName(): string
    {
        return $this->reportName;
    }

    public function setStatus(string $status): void
    {
        if ($this->status === ReportInterface::STATUS_FAILED) {
            return;
        }

        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function addDiagnostic(DiagnosticBuffer $diagnostic): void
    {
        $this->diagnostics[] = $diagnostic;
    }

    /**
     * @return Generator|DiagnosticBuffer[]
     *
     * @psalm-return Generator<int, DiagnosticBuffer, mixed, void>
     */
    public function getDiagnostics(): Generator
    {
        foreach ($this->diagnostics as $diagnostic) {
            yield $diagnostic;
        }
    }

    public function addAttachment(AttachmentBuffer $buffer): void
    {
        $this->attachments[] = $buffer;
    }

    public function addDiff(DiffBuffer $buffer): void
    {
        $this->diffs[] = $buffer;
    }

    /**
     * Get attachments.
     *
     * @return AttachmentBuffer[]
     * @psalm-return list<AttachmentBuffer>
     */
    public function getAttachments(): array
    {
        return array_values($this->attachments);
    }

    /**
     * Get diffs.
     *
     * @return DiffBuffer[]
     * @psalm-return list<DiffBuffer>
     */
    public function getDiffs(): array
    {
        return array_values($this->diffs);
    }

    /** @psalm-return TTaskReportSummary */
    public function countDiagnosticsGroupedBySeverity(): array
    {
        /** @psalm-var TTaskReportSummary $summary */
        $summary = [
            TaskReportInterface::SEVERITY_FATAL    => 0,
            TaskReportInterface::SEVERITY_MAJOR    => 0,
            TaskReportInterface::SEVERITY_MINOR    => 0,
            TaskReportInterface::SEVERITY_MARGINAL => 0,
            TaskReportInterface::SEVERITY_INFO     => 0,
            TaskReportInterface::SEVERITY_NONE     => 0,
        ];

        foreach ($this->getDiagnostics() as $diagnostic) {
            $summary[$diagnostic->getSeverity()]++;
        }

        return $summary;
    }
}
