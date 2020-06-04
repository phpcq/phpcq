<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use DOMElement;
use Generator;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\ReportInterface;
use Phpcq\Report\Buffer\ReportBuffer;

final class FileReportWriter extends AbstractReportWriter
{
    public const XML_NAMESPACE = 'https://phpcq.github.io/v1/file-report.xsd';
    public const ROOT_NODE_NAME = 'file-report';
    public const REPORT_FILE = '/file-report.xml';

    /**
     * @var Generator|DiagnosticIteratorEntry[]
     * @psalm-var Generator<int, DiagnosticIteratorEntry>
     */
    private $diagnostics;

    /**
     * @var DOMElement[]
     * @psalm-var array<string,DOMElement>
     */
    private $fileElements = [];

    public static function writeReport(string $targetPath, ReportBuffer $report): void
    {
        if ($report->getStatus() === ReportInterface::STATUS_STARTED) {
            throw new RuntimeException('Only completed reports may be saved');
        }

        $instance = new static($targetPath, $report);
        $instance->save();
    }

    private function __construct(string $targetPath, ReportBuffer $report)
    {
        parent::__construct($targetPath, $report);

        $this->diagnostics = DiagnosticIterator::sortByFileAndRange($this->report)->getIterator();
    }

    protected function appendReportXml(DOMElement $rootNode): void
    {
        $abstractNode       = $this->xml->createElement('abstract', $rootNode);
        $this->fileElements = [];

        foreach ($this->report->getToolReports() as $toolReport) {
            $tool = $this->xml->createElement('tool', $abstractNode);
            $tool->setAttribute('name', $toolReport->getToolName());
            $tool->setAttribute('status', $toolReport->getStatus());

            $this->appendAttachments($tool, $toolReport);
        }

        $globalNode = $this->xml->createElement('global', $rootNode);
        $filesNode = $this->xml->createElement('files', $rootNode);

        if ($this->diagnostics->valid()) {
            do {
                /** @var DiagnosticIteratorEntry $entry */
                $entry = $this->diagnostics->current();

                if (null !== ($fileName = $entry->getFileName())) {
                    $this->appendDiagnostic($entry, $this->getFileElement($fileName, $filesNode));
                } else {
                    $this->appendDiagnostic($entry, $globalNode);
                }

                $this->diagnostics->next();
            } while ($this->diagnostics->valid());
        }
    }

    private function appendDiagnostic(DiagnosticIteratorEntry $entry, DOMElement $parentNode): void
    {
        $diagnosticElement = $this->createDiagnosticElement($parentNode, $entry);
        $diagnosticElement->setAttribute('tool', $entry->getTool()->getToolName());
    }

    private function getFileElement(string $fileName, DOMElement $parentNode): DOMElement
    {
        if (!isset($this->fileElements[$fileName])) {
            $element = $this->xml->createElement('file', $parentNode);
            $element->setAttribute('name', $fileName);

            $this->fileElements[$fileName] = $element;
        }

        return $this->fileElements[$fileName];
    }
}
