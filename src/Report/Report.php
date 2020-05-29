<?php

declare(strict_types=1);

namespace Phpcq\Report;

use DateTimeImmutable;
use Phpcq\PluginApi\Version10\CheckstyleFileInterface;
use Phpcq\PluginApi\Version10\ReportInterface;

use function array_values;

final class Report implements ReportInterface
{
    /** @var string */
    private $status = 'started';

    /** @var DateTimeImmutable */
    private $startedAt;

    /** @var DateTimeImmutable|null */
    private $completedAt;

    /**
     * @psalm-var array<string,ToolReport>
     * @var ToolReport[]
     */
    private $toolReports = [];

    /**
     * @psalm-var array<string,CheckstyleFile>
     * @var CheckstyleFile[]
     */
    private $checkstyleFiles = [];

    public function __construct()
    {
        $this->startedAt = new DateTimeImmutable();
    }

    public function addToolReport(
        string $toolName,
        string $status,
        string $output = null,
        array $attachments = []
    ): void {
        if (!isset($this->toolReports[$toolName])) {
            $this->toolReports[$toolName] = new ToolReport($toolName);
        }

        $toolReport = $this->toolReports[$toolName];
        $toolReport->setStatus($status);

        if ($output) {
            $toolReport->addOutput($output);
        }

        foreach ($attachments as $attachment) {
            $toolReport->addAttachment($attachment);
        }
    }

    public function addCheckstyle(string $fileName): CheckstyleFileInterface
    {
        if (!isset($this->checkstyleFiles[$fileName])) {
            $this->checkstyleFiles[$fileName] = new CheckstyleFile($fileName);
        }

        return $this->checkstyleFiles[$fileName];
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
        return $this->startedAt;
    }

    /**
     * @return ToolReport[]|iterable
     */
    public function getToolReports(): iterable
    {
        return array_values($this->toolReports);
    }

    /**
     * @return CheckstyleFileInterface[]
     */
    public function getCheckstyleFiles(): iterable
    {
        return array_values($this->checkstyleFiles);
    }

    public function complete(string $status): void
    {
        $this->status      = $status;
        $this->completedAt = new DateTimeImmutable();
    }
}
