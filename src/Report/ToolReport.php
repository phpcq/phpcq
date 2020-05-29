<?php

declare(strict_types=1);

namespace Phpcq\Report;

final class ToolReport
{
    /** @var string */
    private $command;

    /**
     * @var string
     */
    private $status;

    /** @var string[] */
    private $output = [];

    /** @var string[] */
    private $attachments = [];

    public function __construct(string $toolName)
    {
        $this->command     = $toolName;
        $this->status      = Report::STATUS_STARTED;
    }

    /**
     * Get toolName.
     *
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    public function setStatus(string $status): void
    {
        if ($this->status === Report::STATUS_FAILED) {
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

    public function addOutput(string $output): void
    {
        $this->output[] = $output;
    }

    /**
     * Get output.
     *
     * @return string[]
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    public function addAttachment(string $attachment): void
    {
        $this->attachments[] = $attachment;
    }

    /**
     * Get attachments.
     *
     * @return string[]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }
}
