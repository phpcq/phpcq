<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\AttachmentBuffer;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\DiffBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Buffer\ToolReportBuffer */
class ToolReportBufferTest extends TestCase
{
    public function testConstructionCreatesEmpty(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $this->assertSame('report-name', $buffer->getReportName());
        $this->assertSame('tool-name', $buffer->getToolName());
        $this->assertSame('started', $buffer->getStatus());
        $this->assertSame([], iterator_to_array($buffer->getDiagnostics()));
        $this->assertSame([], $buffer->getAttachments());
    }

    public function testSetsStatusToPassed(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');

        $buffer->setStatus('passed');

        $this->assertSame('passed', $buffer->getStatus());
    }

    public function testSetsStatusToFailed(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');

        $buffer->setStatus('failed');

        $this->assertSame('failed', $buffer->getStatus());
    }

    public function testDoesNotSetStatusToPassedWhenAlreadyFailed(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');

        $buffer->setStatus('failed');
        $buffer->setStatus('passed');

        $this->assertSame('failed', $buffer->getStatus());
    }

    public function testAddsDiagnostic(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $buffer->addDiagnostic(
            $diagnostic = new DiagnosticBuffer('error', 'test message', null, null, null, null, null)
        );

        $diagnostics = iterator_to_array($buffer->getDiagnostics());
        $this->assertCount(1, $diagnostics);
        $this->arrayHasKey(0);
        $this->assertSame($diagnostic, $diagnostics[0]);
    }

    public function testAddsAttachment(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $buffer->addAttachment($attachment = new AttachmentBuffer('/some/file', 'local', null));

        $attachments = $buffer->getAttachments();
        $this->assertCount(1, $attachments);
        $this->arrayHasKey(0);
        $this->assertSame($attachment, $attachments[0]);
    }

    public function testAddsDiff(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $buffer->addDiff($diff = new DiffBuffer('/some/file', 'local'));

        $diffs = $buffer->getDiffs();
        $this->assertCount(1, $diffs);
        $this->arrayHasKey(0);
        $this->assertSame($diff, $diffs[0]);
    }

    public function testCountDiagnosticsGroupedBySeverity(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $buffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Info 1', null, null, null, null, null),
        );
        $buffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Info 2', null, null, null, null, null),
        );
        $buffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_NOTICE, 'Notice 1', null, null, null, null, null),
        );
        $buffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_ERROR, 'Error 1', null, null, null, null, null),
        );
        $buffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_ERROR, 'Error 2', null, null, null, null, null),
        );
        $buffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_ERROR, 'Error 3', null, null, null, null, null),
        );

        $this->assertEquals(
            [
                ToolReportInterface::SEVERITY_ERROR => 3,
                ToolReportInterface::SEVERITY_WARNING => 0,
                ToolReportInterface::SEVERITY_NOTICE => 1,
                ToolReportInterface::SEVERITY_INFO => 2,
            ],
            $buffer->countDiagnosticsGroupedBySeverity()
        );
    }
}
