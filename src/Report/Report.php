<?php

declare(strict_types=1);

namespace Phpcq\Report;

use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\CheckstyleFileInterface;
use Phpcq\PluginApi\Version10\ReportInterface;

use function array_values;
use function assert;

use const DATE_ATOM;

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

    public function getToolReports(): iterable
    {
        return array_values($this->toolReports);
    }

    public function getCheckstyleFiles(): iterable
    {
        return array_values($this->checkstyleFiles);
    }

    public function complete(string $status): void
    {
        $this->status      = $status;
        $this->completedAt = new DateTimeImmutable();
    }

    public function save(string $artifactDir): array
    {
        if ($this->status === self::STATUS_STARTED) {
            throw new RuntimeException('Only completed reports may be saved');
        }

        $this->saveReportXml($artifactDir);
        $this->saveCheckstyleXml($artifactDir);

        return [
            'report.xml',
            'checkstyle.xml'
        ];
    }

    private function saveReportXml(string $artifactDir): void
    {
        $completedAt = $this->getCompletedAt();
        assert($completedAt instanceof DateTimeImmutable);

        $reportDocument = new DOMDocument('1.0');
        $rootNode = $reportDocument->appendChild(new DOMElement('report'));

        $rootNode->appendChild(new DOMElement('status', $this->getStatus()));
        $rootNode->appendChild(new DOMElement('started_at', $this->getStartedAt()->format(DATE_ATOM)));
        $rootNode->appendChild(new DOMElement('completed_at', $completedAt->format(DATE_ATOM)));
        $rootNode->appendChild(new DOMElement('checkstyleFile', 'checkstyle.xml'));

        $outputNode = $rootNode->appendChild(new DOMElement('tools'));
        foreach ($this->toolReports as $output) {
            $output->appendToXml($outputNode);
        }

        $reportDocument->formatOutput = true;
        $reportDocument->save($artifactDir . '/report.xml');
    }

    private function saveCheckstyleXml(string $artifactDir): void
    {
        $xmlDocument = new DOMDocument('1.0');
        $rootNode = $xmlDocument->appendChild(new DOMElement('checkstyle'));

        foreach ($this->checkstyleFiles as $file) {
            $file->appendToXml($rootNode);
        }

        $xmlDocument->formatOutput = true;
        $xmlDocument->save($artifactDir . '/checkstyle.xml');
    }
}
