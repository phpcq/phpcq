<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Report\AttachmentBuilderInterface;
use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\Report\DiffBuilderInterface;
use Phpcq\PluginApi\Version10\Report\ToolReportInterface;
use Phpcq\Report\Buffer\AttachmentBuffer;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\DiffBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @psalm-type TDiagnosticSeverity = ToolReportInterface::SEVERITY_NONE
 * |ToolReportInterface::SEVERITY_INFO
 * |ToolReportInterface::SEVERITY_MARGINAL
 * |ToolReportInterface::SEVERITY_MINOR
 * |ToolReportInterface::SEVERITY_MAJOR
 * |ToolReportInterface::SEVERITY_FATAL
 */
class ToolReport implements ToolReportInterface
{
    /** @var ToolReportBuffer */
    private $report;

    /** @var string */
    private $tempDir;

    /** @var Filesystem */
    private $filesystem;

    /** @var DiagnosticBuilder[] */
    private $pendingDiagnostics = [];

    /** @var AttachmentBuilder[] */
    private $pendingAttachments = [];

    /** @var DiffBuilder[] */
    private $pendingDiffs = [];

    public function __construct(ToolReportBuffer $report, string $tempDir, Filesystem $filesystem = null)
    {
        $this->report     = $report;
        $this->tempDir    = $tempDir;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function getStatus(): string
    {
        return $this->report->getStatus();
    }

    public function addDiagnostic(string $severity, string $message): DiagnosticBuilderInterface
    {
        /** @psalm-var TDiagnosticSeverity $severity */
        $builder = new DiagnosticBuilder(
            $this,
            $severity,
            $message,
            function (DiagnosticBuffer $diagnostic, DiagnosticBuilder $builder) {
                $this->report->addDiagnostic($diagnostic);
                unset($this->pendingDiagnostics[spl_object_hash($builder)]);
            }
        );
        return $this->pendingDiagnostics[spl_object_hash($builder)] = $builder;
    }

    public function addAttachment(string $name): AttachmentBuilderInterface
    {
        $builder = new AttachmentBuilder(
            $name,
            $this,
            $this->tempDir,
            $this->filesystem,
            function (AttachmentBuffer $attachment, AttachmentBuilder $builder) {
                $this->report->addAttachment($attachment);
                unset($this->pendingAttachments[spl_object_hash($builder)]);
            }
        );
        return $this->pendingAttachments[spl_object_hash($builder)] = $builder;
    }

    public function addDiff(string $name): DiffBuilderInterface
    {
        $builder = new DiffBuilder(
            $name,
            $this,
            $this->tempDir,
            $this->filesystem,
            function (DiffBuffer $diff, DiffBuilder $builder) {
                $this->report->addDiff($diff);
                unset($this->pendingDiffs[spl_object_hash($builder)]);
            }
        );

        return $this->pendingDiffs[spl_object_hash($builder)] = $builder;
    }

    public function close(string $status): void
    {
        foreach ($this->pendingDiagnostics as $pendingBuilder) {
            $pendingBuilder->end();
        }
        foreach ($this->pendingAttachments as $pendingBuilder) {
            $pendingBuilder->end();
        }
        foreach ($this->pendingDiffs as $pendingBuilder) {
            $pendingBuilder->end();
        }

        $this->report->setStatus($status);
    }
}
