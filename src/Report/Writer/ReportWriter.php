<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use DateTimeImmutable;
use DOMElement;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\ReportInterface;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use Symfony\Component\Filesystem\Filesystem;

use function assert;

use const DATE_ATOM;

/**
 * Write reports to a file.
 */
final class ReportWriter
{
    public const XML_NAMESPACE = 'https://phpcq.github.io/v1/report.xsd';
    public const ROOT_NODE_NAME = 'report';

    /** @var ReportBuffer */
    protected $report;

    /** @var XmlBuilder */
    protected $xml;

    /** @var string $targetPath */
    private $targetPath;

    /** @var Filesystem */
    private $filesystem;

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
        $this->targetPath = $targetPath;
        $this->report     = $report;
        $this->xml        = new XmlBuilder($targetPath, 'phpcq:' . static::ROOT_NODE_NAME, static::XML_NAMESPACE);
        $this->filesystem = new Filesystem();
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

        $outputNode = $this->xml->createElement('tools', $rootNode);
        foreach ($this->report->getToolReports() as $output) {
            $this->appendToolReport($outputNode, $output);
        }

        $this->xml->write('/report.xml');
    }

    private function appendToolReport(DOMElement $node, ToolReportBuffer $report): void
    {
        $tool = $this->xml->createElement('tool', $node);
        $tool->setAttribute('name', $report->getToolName());
        $tool->setAttribute('status', $report->getStatus());

        $errors = $this->xml->createElement('diagnostics', $tool);
        // FIXME: sort by file name.
        foreach ($report->getFiles() as $file) {
            $filePath = $file->getFilePath();
            // FIXME: sort by line number and column.
            foreach ($file as $error) {
                $errorElement = $this->xml->createElement('diagnostic', $errors);

                if ($line = $error->getLine()) {
                    $this->xml->setAttribute($errorElement, 'line', (string)$line);
                }

                if ($column = $error->getColumn()) {
                    $this->xml->setAttribute($errorElement, 'column', (string)$column);
                }
                $this->xml->setAttribute($errorElement, 'file', $filePath);

                $this->xml->setAttribute($errorElement, 'severity', $error->getSeverity());
                if (null !== $source = $error->getSource()) {
                    $this->xml->setAttribute($errorElement, 'source', $source);
                }
                $this->xml->setTextContent($errorElement, $error->getMessage());
            }
        }

        $this->appendAttachments($tool, $report);
    }

    private function appendAttachments(DOMElement $toolNode, ToolReportBuffer $report): void
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

            $this->filesystem->rename($attachment->getAbsolutePath(), $this->targetPath . '/' . $fileName, true);
            // FIXME: better embedd the file instead of copy to the target dir?
            // $this->xml->setTextContent($node, file_get_contents($attachment->getAbsolutePath()));
        }
    }
}
