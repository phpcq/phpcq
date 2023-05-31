<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use DOMElement;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;

/**
 * Write reports to a file in checkstyle format.
 */
final class CheckstyleReportWriter implements ReportWriterInterface
{
    public const ROOT_NODE_NAME = 'checkstyle';

    /**
     * @var DiagnosticIterator
     */
    protected $diagnostics;

    /**
     * @var XmlBuilder
     */
    protected $xml;

    public static function writeReport(string $targetPath, ReportBuffer $report, string $minimumSeverity): void
    {
        if ($report->getStatus() === ReportInterface::STATUS_STARTED) {
            throw new RuntimeException('Only completed reports may be saved');
        }

        $instance = new static($targetPath, $report, $minimumSeverity);
        $instance->save();
    }

    private function __construct(string $targetPath, ReportBuffer $report, string $minimumSeverity)
    {
        $this->xml    = new XmlBuilder($targetPath, static::ROOT_NODE_NAME, null);
        $this->diagnostics = DiagnosticIterator::filterByMinimumSeverity($report, $minimumSeverity)
            ->thenSortByFileAndRange()
            ->thenSortByTool();
    }

    public function save(): void
    {
        $fileElement = null;
        foreach ($this->diagnostics as $entry) {
            $fileElement = $this->updateFileElement($entry, $fileElement);
            $this->writeDiagnostic($fileElement, $entry);
        }

        $this->xml->write('checkstyle.xml');
    }

    private function updateFileElement(DiagnosticIteratorEntry $entry, ?DOMElement $fileElement): DOMElement
    {
        $shouldFile = $entry->getFileName();
        if (null !== $fileElement && $shouldFile === $this->xml->getAttribute($fileElement, 'name')) {
            return $fileElement;
        }

        $fileElement = $this->xml->createElement('file', $this->xml->getDocumentElement());
        if (null !== $shouldFile) {
            $this->xml->setAttribute($fileElement, 'name', $shouldFile);
        }

        return $fileElement;
    }

    private function writeDiagnostic(DOMElement $fileElement, DiagnosticIteratorEntry $entry): void
    {
        $node = $this->xml->createElement('error', $fileElement);

        if (null !== $range = $entry->getRange()) {
            if ($line = $range->getStartLine()) {
                $this->xml->setAttribute($node, 'line', (string) $line);
            }
            if ($column = $range->getStartColumn()) {
                $this->xml->setAttribute($node, 'column', (string) $column);
            }
        }

        $error = $entry->getDiagnostic();
        $this->xml->setAttribute($node, 'severity', $error->getSeverity());
        $this->xml->setAttribute($node, 'message', $error->getMessage());

        $source = $error->getSource();

        $toolName = $entry->getTask()->getTaskName();
        $this->xml->setAttribute($node, 'source', null !== $source ? sprintf('%s: %s', $toolName, $source) : $toolName);
    }
}
