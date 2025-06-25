<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use DOMElement;
use Generator;
use Phpcq\Runner\Report\Buffer\ReportBuffer;

/**
 * Write reports to a file.
 */
final class TaskReportWriter extends AbstractReportWriter
{
    public const XML_NAMESPACE = 'https://phpcq.github.io/schema/v1/task-report.xsd';
    public const ROOT_NODE_NAME = 'task-report';
    public const REPORT_FILE = '/task-report.xml';

    /**
     * @var Generator|DiagnosticIteratorEntry[]
     * @var Generator<int, DiagnosticIteratorEntry>
     */
    private readonly Generator|array $diagnostics;

    protected function __construct(string $targetPath, ReportBuffer $report, string $minimumSeverity)
    {
        parent::__construct($targetPath, $report, $minimumSeverity);

        $this->diagnostics = DiagnosticIterator::filterByMinimumSeverity($report, $minimumSeverity)
            ->thenSortByTool()
            ->thenSortByFileAndRange()
            ->getIterator();
    }

    #[\Override]
    protected function appendReportXml(DOMElement $rootNode): void
    {
        $outputNode = $this->xml->createElement('tasks', $rootNode);

        if ($this->diagnostics->valid()) {
            do {
                $this->appendTaskReport($outputNode);
            } while ($this->diagnostics->valid());
        }
    }

    #[\Override]
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

    private function appendTaskReport(DOMElement $node): void
    {
        /** @var DiagnosticIteratorEntry $entry */
        $entry = $this->diagnostics->current();
        $report = $entry->getTask();

        $task = $this->xml->createElement('task', $node);
        $task->setAttribute('name', $report->getTaskName());
        $task->setAttribute('status', $report->getStatus());
        $task->setAttribute('version', $report->getMetadata()['tool_version'] ?? 'unknown');
        $diagnosticsElement = $this->xml->createElement('diagnostics', $task);
        do {
            $this->createDiagnosticElement($diagnosticsElement, $entry);
            if (!$this->diagnostics->valid()) {
                break;
            }
            $entry = $this->diagnostics->current();
        } while ($entry !== null && $this->diagnostics->valid() && $report === $entry->getTask());

        $this->appendAttachments($task, $report);
        $this->appendDiffs($task, $report);
    }
}
