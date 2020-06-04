<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
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

    public function addAttachment(string $filePath, ?string $name = null): void
    {
        $this->report->addAttachment($filePath, $name);
    }

    public function addBufferAsAttachment(string $buffer, string $name): void
    {
        $filePath = $this->tempDir . '/' . uniqid($name);

        $this->filesystem->dumpFile($filePath, $buffer);

        $this->addAttachment($filePath, $name);
    }

    public function finish(string $status): void
    {
        foreach ($this->pendingDiagnostics as $pendingBuilder) {
            $pendingBuilder->end();
        }

        $this->report->setStatus($status);
    }
}
