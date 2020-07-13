<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use DOMElement;
use Generator;
use Phpcq\Report\Buffer\ReportBuffer;

/**
 * Write reports to a file.
 */
final class ToolReportWriter extends AbstractReportWriter
{
    public const XML_NAMESPACE = 'https://phpcq.github.io/schema/v1/tool-report.xsd';
    public const ROOT_NODE_NAME = 'tool-report';
    public const REPORT_FILE = '/tool-report.xml';

    /**
     * @var Generator|DiagnosticIteratorEntry[]
     * @psalm-var Generator<int, DiagnosticIteratorEntry>
     */
    private $diagnostics;

    protected function __construct(string $targetPath, ReportBuffer $report, string $minimumSeverity)
    {
        parent::__construct($targetPath, $report, $minimumSeverity);

        $this->diagnostics = DiagnosticIterator::filterByMinimumSeverity($report, $minimumSeverity)
            ->thenSortByTool()
            ->thenSortByFileAndRange()
            ->getIterator();
    }

    protected function appendReportXml(DOMElement $rootNode): void
    {
        $outputNode = $this->xml->createElement('tools', $rootNode);

        if ($this->diagnostics->valid()) {
            do {
                $this->appendToolReport($outputNode);
            } while ($this->diagnostics->valid());
        }
    }

    protected function handleRange(DOMElement $diagnosticElement, DiagnosticIteratorEntry $entry): void
    {
        if (!$entry->getDiagnostic()->hasFileRanges()) {
            $this->diagnostics->next();
            return;
        }

        // Collect ranges for this diagnostic together.
        $diagnostic = $entry->getDiagnostic();
        do {
            $range = $entry->getRange();
            if (null === $range) {
                break;
            }
            $fileElement = $this->xml->createElement('file', $diagnosticElement);
            parent::handleRange($fileElement, $entry);
            $this->xml->setAttribute($fileElement, 'name', $range->getFile());
            $this->diagnostics->next();
            /** @var DiagnosticIteratorEntry $entry */
            $entry = $this->diagnostics->current();
        } while ($this->diagnostics->valid() && $diagnostic === $entry->getDiagnostic());
    }

    private function appendToolReport(DOMElement $node): void
    {
        /** @var DiagnosticIteratorEntry $entry */
        $entry = $this->diagnostics->current();
        $report = $entry->getTool();

        $tool = $this->xml->createElement('tool', $node);
        $tool->setAttribute('name', $report->getToolName());
        $tool->setAttribute('status', $report->getStatus());
        $tool->setAttribute('version', $report->getToolVersion());
        $diagnosticsElement = $this->xml->createElement('diagnostics', $tool);
        do {
            $this->createDiagnosticElement($diagnosticsElement, $entry);
            if (!$this->diagnostics->valid()) {
                break;
            }
            $entry = $this->diagnostics->current();
        } while ($this->diagnostics->valid() && $report === $entry->getTool());

        $this->appendAttachments($tool, $report);
        $this->appendDiffs($tool, $report);
    }
}
