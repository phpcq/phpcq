<?php

declare(strict_types=1);

namespace Phpcq\Report\Buffer;

use Phpcq\PluginApi\Version10\ReportInterface;

final class ToolReportBuffer
{
    /** @var string */
    private $toolName;

    /** @var string */
    private $status;

    /** @var SourceFileBuffer[] */
    private $files = [];

    /** @var AttachmentBuffer[] */
    private $attachments = [];

    public function __construct(string $toolName)
    {
        $this->toolName = $toolName;
        $this->status   = ReportInterface::STATUS_STARTED;
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

    /**
     * Get a file buffer (file path is relative to project root).
     */
    public function getFile(string $filePath): SourceFileBuffer
    {
        if (!isset($this->files[$filePath])) {
            $this->files[$filePath] = new SourceFileBuffer($filePath);
        }

        return $this->files[$filePath];
    }

    /**
     * @return SourceFileBuffer[]|iterable
     *
     * @psalm-return list<SourceFileBuffer>
     */
    public function getFiles(): iterable
    {
        return array_values($this->files);
    }

    public function addAttachment(string $filePath, ?string $name = null): void
    {
        $this->attachments[] = new AttachmentBuffer($filePath, $name);
    }

    /**
     * Get attachments.
     *
     * @return AttachmentBuffer[]
     */
    public function getAttachments(): array
    {
        return array_values($this->attachments);
    }
}
