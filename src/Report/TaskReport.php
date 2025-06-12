<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report;

use Phpcq\PluginApi\Version10\Report\AttachmentBuilderInterface;
use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\Report\DiffBuilderInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\AttachmentBuffer;
use Phpcq\Runner\Report\Buffer\DiagnosticBuffer;
use Phpcq\Runner\Report\Buffer\DiffBuffer;
use Phpcq\Runner\Report\Buffer\TaskReportBuffer;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @psalm-type TDiagnosticSeverity = TaskReportInterface::SEVERITY_NONE|TaskReportInterface::SEVERITY_INFO|TaskReportInterface::SEVERITY_MARGINAL|TaskReportInterface::SEVERITY_MINOR|TaskReportInterface::SEVERITY_MAJOR|TaskReportInterface::SEVERITY_FATAL
 */
class TaskReport implements TaskReportInterface
{
    /** @var TaskReportBuffer */
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

    public function __construct(TaskReportBuffer $report, string $tempDir, ?Filesystem $filesystem = null)
    {
        $this->report     = $report;
        $this->tempDir    = $tempDir;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    #[\Override]
    public function getStatus(): string
    {
        return $this->report->getStatus();
    }

    #[\Override]
    public function addMetadata(string $name, string $value): TaskReportInterface
    {
        $this->report->addMetadata($name, $value);

        return $this;
    }

    #[\Override]
    public function addDiagnostic(string $severity, string $message): DiagnosticBuilderInterface
    {
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

    #[\Override]
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

    #[\Override]
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

    #[\Override]
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
