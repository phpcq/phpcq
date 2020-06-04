<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use Phpcq\Report\Buffer\AttachmentBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Buffer\ToolReportBuffer */
class ToolReportBufferTest extends TestCase
{
    public function testConstructionCreatesEmpty(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');
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

    public function testAddsAttachment(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');

        $buffer->addAttachment('/some/file', 'local');

        $attachments = $buffer->getAttachments();

        $this->assertCount(1, $attachments);
        $this->arrayHasKey(0);
        $attachment = $attachments[0];
        $this->assertInstanceOf(AttachmentBuffer::class, $attachment);
        $this->assertSame('/some/file', $attachment->getAbsolutePath());
        $this->assertSame('local', $attachment->getLocalName());
    }

    public function testAddsAttachmentWithNulledName(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');

        $buffer->addAttachment('/some/file');

        $attachments = $buffer->getAttachments();
        $this->assertCount(1, $attachments);
        $this->arrayHasKey(0);
        $attachment = $attachments[0];
        $this->assertInstanceOf(AttachmentBuffer::class, $attachment);
        $this->assertSame('/some/file', $attachment->getAbsolutePath());
        $this->assertSame('file', $attachment->getLocalName());
    }
}
