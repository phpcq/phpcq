<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\PluginApi\Version10\Report\AttachmentBuilderInterface;
use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\AttachmentBuffer;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use Symfony\Component\Filesystem\Filesystem;

class ToolReport implements ToolReportInterface
{
    /** @var string */
    private $toolName;

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

    public function __construct(
        string $toolName,
        ToolReportBuffer $report,
        string $tempDir,
        Filesystem $filesystem = null
    ) {
        $this->toolName   = $toolName;
        $this->report     = $report;
        $this->tempDir    = $tempDir;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

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

    public function finish(string $status): void
    {
        foreach ($this->pendingDiagnostics as $pendingBuilder) {
            $pendingBuilder->end();
        }
        foreach ($this->pendingAttachments as $pendingBuilder) {
            $pendingBuilder->end();
        }

        $this->report->setStatus($status);
    }
}
