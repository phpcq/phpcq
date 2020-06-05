<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use DateTimeImmutable;
use DOMElement;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\ReportInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use Symfony\Component\Filesystem\Filesystem;

use function assert;

use const DATE_ATOM;

abstract class AbstractReportWriter
{
    public const XML_NAMESPACE = '';
    public const ROOT_NODE_NAME = '';
    public const REPORT_FILE = '';

    private const SEVERITY_LOOKUP = [
        ToolReportInterface::SEVERITY_INFO    => 0,
        ToolReportInterface::SEVERITY_NOTICE  => 1,
        ToolReportInterface::SEVERITY_WARNING => 2,
        ToolReportInterface::SEVERITY_ERROR   => 3,
    ];

    /** @var ReportBuffer */
    protected $report;

    /** @var XmlBuilder */
    protected $xml;

    /** @var string $targetPath */
    private $targetPath;

    /** @var Filesystem */
    private $filesystem;

    /** @var int */
    private $minimumSeverity;

    public static function writeReport(
        string $targetPath,
        ReportBuffer $report,
        string $minimumSeverity = ToolReportInterface::SEVERITY_INFO
    ): void {
        if ($report->getStatus() === ReportInterface::STATUS_STARTED) {
            throw new RuntimeException('Only completed reports may be saved');
        }

        $instance = new static($targetPath, $report, $minimumSeverity);
        $instance->save();
    }

    protected function __construct(string $targetPath, ReportBuffer $report, string $minimumSeverity)
    {
        $this->targetPath = $targetPath;
        $this->report     = $report;
        $this->xml        = new XmlBuilder($targetPath, 'phpcq:' . static::ROOT_NODE_NAME, static::XML_NAMESPACE);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->targetPath);
        $this->minimumSeverity = self::SEVERITY_LOOKUP[$minimumSeverity];
    }

    protected function save(): void
    {
        $completedAt = $this->report->getCompletedAt();
        assert($completedAt instanceof DateTimeImmutable);

        $rootNode = $this->xml->getDocumentElement();

        $this->xml->setAttribute($rootNode, 'status', $this->report->getStatus());
        $this->xml->setAttribute($rootNode, 'started_at', $this->report->getStartedAt()->format(DATE_ATOM));
        $this->xml->setAttribute($rootNode, 'completed_at', $completedAt->format(DATE_ATOM));

        $this->appendReportXml($rootNode);

        $this->xml->write(static::REPORT_FILE);
    }

    abstract protected function appendReportXml(DOMElement $rootNode): void;

    protected function handleRange(DOMElement $diagnosticElement, DiagnosticIteratorEntry $entry): void
    {
        if (null === $range = $entry->getRange()) {
            return;
        }
        if (null !== $int = $range->getStartLine()) {
            $this->xml->setAttribute($diagnosticElement, 'line', (string)$int);
        }
        if (null !== $int = $range->getStartColumn()) {
            $this->xml->setAttribute($diagnosticElement, 'column', (string)$int);
        }
        if (null !== $int = $range->getEndLine()) {
            $this->xml->setAttribute($diagnosticElement, 'line_end', (string)$int);
        }
        if (null !== $int = $range->getEndColumn()) {
            $this->xml->setAttribute($diagnosticElement, 'column_end', (string)$int);
        }
    }

    protected function wantsToReport(DiagnosticIteratorEntry $entry): bool
    {
        return self::SEVERITY_LOOKUP[$entry->getDiagnostic()->getSeverity()] >= $this->minimumSeverity;
    }

    protected function createDiagnosticElement(
        DOMElement $parentNode,
        DiagnosticIteratorEntry $entry
    ): DOMElement {
        $diagnosticElement = $this->xml->createElement('diagnostic', $parentNode);
        $this->handleRange($diagnosticElement, $entry);
        $diagnostic = $entry->getDiagnostic();
        $this->xml->setAttribute($diagnosticElement, 'severity', $diagnostic->getSeverity());
        if (null !== $source = $diagnostic->getSource()) {
            $this->xml->setAttribute($diagnosticElement, 'source', $source);
        }
        $this->xml->setTextContent($diagnosticElement, $diagnostic->getMessage());

        return $diagnosticElement;
    }

    protected function appendAttachments(DOMElement $toolNode, ToolReportBuffer $report): void
    {
        $attachments = $report->getAttachments();
        if ([] === $attachments) {
            return;
        }

        $attachmentsNode = $this->xml->createElement('attachments', $toolNode);
        $filePrefix = $report->getToolName() . '-';
        foreach ($attachments as $attachment) {
            $absolutePath = $attachment->getAbsolutePath();
            if (!$this->filesystem->exists($absolutePath)) {
                // FIXME: warn if the source does not exist.
                continue;
            }

            $node = $this->xml->createElement('attachment', $attachmentsNode);
            $this->xml->setAttribute($node, 'name', $attachment->getLocalName());
            $this->xml->setAttribute($node, 'filename', $fileName = $filePrefix . $attachment->getLocalName());

            $this->filesystem->copy($absolutePath, $this->targetPath . '/' . $fileName, true);
            // FIXME: better embedd the file instead of copy to the target dir?
            // $this->xml->setTextContent($node, file_get_contents($attachment->getAbsolutePath()));
        }
    }
}
