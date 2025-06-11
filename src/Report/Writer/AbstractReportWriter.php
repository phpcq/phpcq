<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use DateTimeImmutable;
use DOMElement;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\DiagnosticBuffer;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\Buffer\TaskReportBuffer;
use Symfony\Component\Filesystem\Filesystem;

use function assert;

use const DATE_ATOM;

abstract class AbstractReportWriter implements ReportWriterInterface
{
    /** @var string */
    public const XML_NAMESPACE = '';
    /** @var string */
    public const ROOT_NODE_NAME = '';
    /** @var string */
    public const REPORT_FILE = '';

    /** @var ReportBuffer */
    protected $report;

    /** @var XmlBuilder */
    protected $xml;

    /** @var string $targetPath */
    private $targetPath;

    /** @var Filesystem */
    private $filesystem;

    #[\Override]
    public static function writeReport(
        string $targetPath,
        ReportBuffer $report,
        string $minimumSeverity = TaskReportInterface::SEVERITY_INFO
    ): void {
        if ($report->getStatus() === ReportInterface::STATUS_STARTED) {
            throw new RuntimeException('Only completed reports may be saved');
        }

        /** @psalm-suppress UnsafeInstantiation */
        $instance = new static($targetPath, $report, $minimumSeverity);
        $instance->save();
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) - Implementations may use the parameter */
    protected function __construct(string $targetPath, ReportBuffer $report, string $minimumSeverity)
    {
        $this->targetPath  = $targetPath;
        $this->report      = $report;
        /**
         * @psalm-suppress MixedOperand - No way to indicate type of static references constants
         * @psalm-suppress MixedArgument
         */
        $this->xml         = new XmlBuilder($targetPath, 'phpcq:' . static::ROOT_NODE_NAME, static::XML_NAMESPACE);
        $this->filesystem  = new Filesystem();
        $this->filesystem->mkdir($this->targetPath);
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

        /** @psalm-suppress MixedArgument */
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

    protected function createDiagnosticElement(
        DOMElement $parentNode,
        DiagnosticIteratorEntry $entry
    ): DOMElement {
        $diagnosticElement = $this->xml->createElement('diagnostic', $parentNode);
        $diagnostic = $entry->getDiagnostic();
        $this->appendClassNames($diagnosticElement, $diagnostic);
        $this->appendCategories($diagnosticElement, $diagnostic);
        $this->handleRange($diagnosticElement, $entry);
        $this->xml->setAttribute($diagnosticElement, 'severity', $diagnostic->getSeverity());
        if (null !== $source = $diagnostic->getSource()) {
            $this->xml->setAttribute($diagnosticElement, 'source', $source);
        }
        if (null !== $externalInfoUrl = $diagnostic->getExternalInfoUrl()) {
            $this->xml->setAttribute($diagnosticElement, 'external_info_url', $externalInfoUrl);
        }

        $this->xml->setTextContent(
            $this->xml->createElement('message', $diagnosticElement),
            $diagnostic->getMessage()
        );

        return $diagnosticElement;
    }

    protected function appendClassNames(DOMElement $node, DiagnosticBuffer $diagnostic): void
    {
        if (!$diagnostic->hasClassNames()) {
            return;
        }
        foreach ($diagnostic->getClassNames() as $category) {
            $this->xml->setAttribute($this->xml->createElement('class_name', $node), 'name', $category);
        }
    }

    protected function appendCategories(DOMElement $node, DiagnosticBuffer $diagnostic): void
    {
        if (!$diagnostic->hasCategories()) {
            return;
        }
        foreach ($diagnostic->getCategories() as $category) {
            $this->xml->setAttribute($this->xml->createElement('category', $node), 'name', $category);
        }
    }

    protected function appendAttachments(DOMElement $toolNode, TaskReportBuffer $report): void
    {
        $attachments = $report->getAttachments();
        if ([] === $attachments) {
            return;
        }

        $attachmentsNode = $this->xml->createElement('attachments', $toolNode);
        $filePrefix = $report->getTaskName() . '-';
        foreach ($attachments as $attachment) {
            $absolutePath = $attachment->getAbsolutePath();
            if (!$this->filesystem->exists($absolutePath)) {
                // FIXME: warn if the source does not exist.
                continue;
            }

            $node = $this->xml->createElement('attachment', $attachmentsNode);
            $this->xml->setAttribute($node, 'name', $attachment->getLocalName());
            $this->xml->setAttribute($node, 'filename', $fileName = $filePrefix . $attachment->getLocalName());
            if (null !== ($mimeType = $attachment->getMimeType())) {
                $this->xml->setAttribute($node, 'mime', $mimeType);
            }

            $this->filesystem->copy($absolutePath, $this->targetPath . '/' . $fileName, true);
            // FIXME: better embedd the file instead of copy to the target dir?
            // $this->xml->setTextContent($node, file_get_contents($attachment->getAbsolutePath()));
        }
    }

    protected function appendDiffs(DOMElement $toolNode, TaskReportBuffer $report): void
    {
        $diffs = $report->getDiffs();
        if ([] === $diffs) {
            return;
        }

        $attachmentsNode = $this->xml->createElement('diffs', $toolNode);
        $filePrefix = $report->getTaskName() . '-';
        foreach ($diffs as $attachment) {
            $absolutePath = $attachment->getAbsolutePath();
            if (!$this->filesystem->exists($absolutePath)) {
                // FIXME: warn if the source does not exist.
                continue;
            }

            $node = $this->xml->createElement('diff', $attachmentsNode);
            $this->xml->setAttribute($node, 'name', $attachment->getLocalName());
            $this->xml->setAttribute($node, 'filename', $fileName = $filePrefix . $attachment->getLocalName());

            $this->filesystem->copy($absolutePath, $this->targetPath . '/' . $fileName, true);
            // FIXME: better embedd the file instead of copy to the target dir?
            // $this->xml->setTextContent($node, file_get_contents($attachment->getAbsolutePath()));
        }
    }
}
