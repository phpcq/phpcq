<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\ToolReportBuffer;
use Symfony\Component\Filesystem\Filesystem;

class ToolReport implements ToolReportInterface
{
    public const UNKNOWN_FILE = 'unknown-file';

    /** @var string */
    private $toolName;

    /** @var ToolReportBuffer */
    private $report;

    /** @var string */
    private $tempDir;

    /** @var Filesystem */
    private $filesystem;

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

    public function addDiagnostic(
        string $severity,
        string $message,
        ?string $file = null,
        ?int $line = null,
        ?int $column = null,
        ?string $source = null
    ): void {
        $this->report
            // FIXME: if we would know the root dir, we could strip it here.
            ->getFile($file ?? self::UNKNOWN_FILE)
            ->addDiagnostic($severity, $message, $source, $line, $column);
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
        $this->report->setStatus($status);
    }
}
