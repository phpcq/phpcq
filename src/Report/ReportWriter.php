<?php

declare(strict_types=1);

namespace Phpcq\Report;

use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMText;
use Phpcq\Exception\RuntimeException;

use function assert;

use const DATE_ATOM;

/**
 * Write reports to a file.
 */
final class ReportWriter
{
    public const XML_NAMESPACE = 'https://phpcq.github.io/v1/report.xsd';

    /**
     * @var string
     */
    private $targetPath;

    /**
     * @var Report
     */
    private $report;

    /**
     * @var DOMDocument
     */
    private $document;

    public static function writeReport(string $targetPath, Report $report): void
    {
        if ($report->getStatus() === Report::STATUS_STARTED) {
            throw new RuntimeException('Only completed reports may be saved');
        }

        $instance = new self($targetPath, $report);
        $instance->saveReportXml();
        $instance->saveCheckstyleXml();
    }

    private function __construct(string $targetPath, Report $report)
    {
        $this->targetPath = $targetPath;
        $this->report     = $report;
        $this->document   = new DOMDocument('1.0');
        $this->document->appendChild($this->createElement('report'));
    }

    private function createElement(string $name, ?DOMElement $parent = null): DOMElement
    {
        $element = $this->document->createElementNS(self::XML_NAMESPACE, $name);
        if (null !== $parent) {
            $parent->appendChild($element);
        }

        return $element;
    }

    private function saveReportXml(): void
    {
        $completedAt = $this->report->getCompletedAt();
        assert($completedAt instanceof DateTimeImmutable);

        $rootNode = $this->document->documentElement;

        $this->createElement('status', $rootNode)->nodeValue = $this->report->getStatus();
        $this->createElement('started_at', $rootNode)->nodeValue = $this->report->getStartedAt()->format(DATE_ATOM);
        $this->createElement('completed_at', $rootNode)->nodeValue = $completedAt->format(DATE_ATOM);
        $this->createElement('checkstyleFile', $rootNode)->nodeValue = 'checkstyle.xml';

        $outputNode = $rootNode->appendChild(new DOMElement('tools'));
        foreach ($this->report->getToolReports() as $output) {
            $this->appendToolReport($outputNode, $output);
        }

        $this->document->formatOutput = true;
        $this->document->save($this->targetPath . '/report.xml');
    }

    private function appendToolReport(DOMElement $node, ToolReport $report)
    {
        $domElement = $this->createElement('tool', $node);
        $domElement->setAttribute('name', $report->getCommand());
        $domElement->setAttribute('status', $report->getStatus());

        foreach ($report->getOutput() as $outputLine) {
            $outputElement = $domElement->appendChild(new DOMElement('output'));
            $outputElement->appendChild(new DOMText($outputLine));
        }

        foreach ($report->getAttachments() as $attachment) {
            $domElement->appendChild(new DOMElement('attachment', $attachment));
        }

        return $domElement;
    }

    private function saveCheckstyleXml(): void
    {
        $xmlDocument = new DOMDocument('1.0');
        $rootNode = $xmlDocument->appendChild(new DOMElement('checkstyle'));

        foreach ($this->report->getCheckstyleFiles() as $file) {
            $this->appendCheckstyle($rootNode, $file);
        }

        $xmlDocument->formatOutput = true;
        $xmlDocument->save($this->targetPath . '/checkstyle.xml');
    }

    public function appendCheckstyle(DOMElement $node, CheckstyleFile $file): void
    {
        $fileElement = $node->appendChild(new DOMElement('file'));
        $fileElement->setAttribute('name', $file->getName());

        foreach ($file->getIterator() as $error) {
            $error->appendToXml($fileElement);
        }
    }
}
