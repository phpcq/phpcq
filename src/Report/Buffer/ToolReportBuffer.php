<?php

declare(strict_types=1);

namespace Phpcq\Report\Buffer;

use Generator;
use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\ToolReportInterface;

/**
 * TODO: Use class constants as key when implemented in psalm https://github.com/vimeo/psalm/issues/3555
 * @psalm-type TToolReportSummary = array{
 *  none: int,
 *  info: int,
 *  marginal: int,
 *  minor: int,
 *  major: int,
 *  fatal: int
 * }
 */
final class ToolReportBuffer
{
    /** @var string */
    private $toolName;

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

    /** @var string */
    private $toolVersion;

    public function __construct(string $toolName, string $reportName, string $toolVersion)
    {
        $this->toolName    = $toolName;
        $this->reportName  = $reportName;
        $this->toolVersion = $toolVersion;
        $this->status      = ReportInterface::STATUS_STARTED;
    }

    /**
     * Get toolName.
     *
     * @return string
     */
    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function getToolVersion(): string
    {
        return $this->toolVersion;
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

    /** @psalm-return TToolReportSummary */
    public function countDiagnosticsGroupedBySeverity(): array
    {
        /** @psalm-var TToolReportSummary $summary */
        $summary = [
            ToolReportInterface::SEVERITY_FATAL    => 0,
            ToolReportInterface::SEVERITY_MAJOR    => 0,
            ToolReportInterface::SEVERITY_MINOR    => 0,
            ToolReportInterface::SEVERITY_MARGINAL => 0,
            ToolReportInterface::SEVERITY_INFO     => 0,
            ToolReportInterface::SEVERITY_NONE     => 0,
        ];

        foreach ($this->getDiagnostics() as $diagnostic) {
            $summary[$diagnostic->getSeverity()]++;
        }

        return $summary;
    }
}
