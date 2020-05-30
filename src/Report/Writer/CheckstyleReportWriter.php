<?php

declare(strict_types=1);

namespace Phpcq\Report\Writer;

use DOMElement;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\ReportInterface;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Buffer\SourceFileError;
use Phpcq\Report\Buffer\ToolReportBuffer;

/**
 * Write reports to a file in checkstyle format.
 */
final class CheckstyleReportWriter
{
    public const ROOT_NODE_NAME = 'checkstyle';

    /**
     * @var SourceFileError[][][]|string[][][]
     * @psalm-var array<string,array<int,array{error: SourceFileError, tool: string}>>
     */
    private $errors = [];

    /**
     * @var ReportBuffer
     */
    protected $report;

    /**
     * @var XmlBuilder
     */
    protected $xml;

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
        $this->report = $report;
        $this->xml    = new XmlBuilder($targetPath, static::ROOT_NODE_NAME, null);
    }

    public function save(): void
    {
        foreach ($this->report->getToolReports() as $output) {
            $this->getErrorsFromToolReport($output);
        }

        ksort($this->errors);
        // Now dump them.
        foreach ($this->errors as $filePath => $errors) {
            $fileElement = $this->xml->createElement('file', $this->xml->getDocumentElement());
            $this->xml->setAttribute($fileElement, 'name', $filePath);
            // Sort errors ascending by line number and column.
            usort($errors, function (array $tupel1, array $tupel2) {
                $error1 = $tupel1['error'];
                $error2 = $tupel2['error'];

                if (($line1 = $error1->getLine()) !== ($line2 = $error2->getLine())) {
                    return $line1 <=> $line2;
                }
                return $error1->getColumn() <=> $error2->getColumn();
            });
            foreach ($errors as $tupel) {
                $this->writeError($fileElement, $tupel['error'], $tupel['tool']);
            }
        }

        $this->xml->write('checkstyle.xml');
    }

    private function getErrorsFromToolReport(ToolReportBuffer $report): void
    {
        $toolName = $report->getToolName();
        foreach ($report->getFiles() as $file) {
            $filePath = $file->getFilePath();
            if (!isset($this->errors[$filePath])) {
                $this->errors[$filePath] = [];
            }
            foreach ($file as $error) {
                $this->errors[$filePath][] = ['error' => $error, 'tool' => $toolName];
            }
        }
    }

    private function writeError(DOMElement $fileElement, SourceFileError $error, string $toolName): void
    {
        $node = $this->xml->createElement('error', $fileElement);

        if ($line = $error->getLine()) {
            $this->xml->setAttribute($node, 'line', (string) $line);
        }

        if ($column = $error->getColumn()) {
            $this->xml->setAttribute($node, 'column', (string) $column);
        }

        $this->xml->setAttribute($node, 'severity', $error->getSeverity());
        $this->xml->setAttribute($node, 'message', $error->getMessage());

        $source = $error->getSource();

        $this->xml->setAttribute($node, 'source', null !== $source ? sprintf('%s: %s', $toolName, $source) : $toolName);
    }
}
