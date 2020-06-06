<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use Phpcq\Report\Buffer\AttachmentBuffer;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Buffer\ToolReportBuffer */
class ToolReportBufferTest extends TestCase
{
    public function testConstructionCreatesEmpty(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');
        $this->assertSame('report-name', $buffer->getReportName());
        $this->assertSame('tool-name', $buffer->getToolName());
        $this->assertSame('started', $buffer->getStatus());
        $this->assertSame([], iterator_to_array($buffer->getDiagnostics()));
        $this->assertSame([], $buffer->getAttachments());
    }

    public function testSetsStatusToPassed(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');

        $buffer->setStatus('passed');

        $this->assertSame('passed', $buffer->getStatus());
    }

    public function testSetsStatusToFailed(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');

        $buffer->setStatus('failed');

        $this->assertSame('failed', $buffer->getStatus());
    }

    public function testDoesNotSetStatusToPassedWhenAlreadyFailed(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');

        $buffer->setStatus('failed');
        $buffer->setStatus('passed');

        $this->assertSame('failed', $buffer->getStatus());
    }

    public function testAddsDiagnostic(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');
        $buffer->addDiagnostic($diagnostic = new DiagnosticBuffer('error', 'test message', null, null));

        $diagnostics = iterator_to_array($buffer->getDiagnostics());
        $this->assertCount(1, $diagnostics);
        $this->arrayHasKey(0);
        $this->assertSame($diagnostic, $diagnostics[0]);
    }

    public function testAddsAttachment(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');
        $buffer->addAttachment($attachment = new AttachmentBuffer('/some/file', 'local', null));

        $attachments = $buffer->getAttachments();
        $this->assertCount(1, $attachments);
        $this->arrayHasKey(0);
        $this->assertSame($attachment, $attachments[0]);
    }
}
