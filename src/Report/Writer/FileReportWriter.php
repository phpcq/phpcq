<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use DOMElement;
use Generator;
use Phpcq\Report\Buffer\ReportBuffer;

final class FileReportWriter extends AbstractReportWriter
{
    public const XML_NAMESPACE = 'https://phpcq.github.io/schema/v1/file-report.xsd';
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

    protected function __construct(string $targetPath, ReportBuffer $report, string $minimumSeverity)
    {
        parent::__construct($targetPath, $report, $minimumSeverity);

        $this->diagnostics = DiagnosticIterator::filterByMinimumSeverity($report, $minimumSeverity)
            ->thenSortByFileAndRange()
            ->getIterator();
    }

    protected function appendReportXml(DOMElement $rootNode): void
    {
        $abstractNode       = $this->xml->createElement('abstract', $rootNode);
        $this->fileElements = [];

        foreach ($this->report->getTaskReports() as $taskReport) {
            $tool = $this->xml->createElement('tool', $abstractNode);
            $tool->setAttribute('name', $taskReport->getTaskName());
            $tool->setAttribute('status', $taskReport->getStatus());
            $tool->setAttribute('version', $taskReport->getMetadata());

            $this->appendAttachments($tool, $taskReport);
            $this->appendDiffs($tool, $taskReport);
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
        $diagnosticElement->setAttribute('tool', $entry->getTool()->getTaskName());
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
