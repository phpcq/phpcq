<?php

declare(strict_types=1);

namespace Phpcq\Test\Report;

use Phpcq\Report\Buffer\AttachmentBuffer;
use Phpcq\Report\Buffer\SourceFileDiagnostic;
use Phpcq\Report\Buffer\ToolReportBuffer;
use Phpcq\Report\ToolReport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/** @covers \Phpcq\Report\ToolReport */
class ToolReportTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        new ToolReport(
            'tool-name',
            new ToolReportBuffer('tool-name'),
            sys_get_temp_dir()
        );
        $this->expectNotToPerformAssertions();
    }

    public function testAddErrorIsDelegated(): void
    {
        $buffer = new ToolReportBuffer('tool-name');
        $report = new ToolReport('tool-name', $buffer, sys_get_temp_dir());

        $report->addDiagnostic(
            'error',
            'This is an error',
            'some/file.php',
            10,
            20,
            'source name'
        );

        $errors = iterator_to_array($buffer->getFile('some/file.php'));
        $this->assertCount(1, $errors);
        /** @var SourceFileDiagnostic $error */
        $error = $errors[0];

        $this->assertSame('error', $error->getSeverity());
        $this->assertSame('This is an error', $error->getMessage());
        $this->assertSame(10, $error->getLine());
        $this->assertSame(20, $error->getColumn());
        $this->assertSame('source name', $error->getSource());
    }

    public function testAddErrorIsDelegatedWhenCalledWithNulledValues(): void
    {
        $buffer = new ToolReportBuffer('tool-name');
        $report = new ToolReport('tool-name', $buffer, sys_get_temp_dir());

        $report->addDiagnostic('error', 'This is an error');

        $errors = iterator_to_array($buffer->getFile(ToolReport::UNKNOWN_FILE));
        $this->assertCount(1, $errors);
        /** @var SourceFileDiagnostic $error */
        $error = $errors[0];

        $this->assertSame('error', $error->getSeverity());
        $this->assertSame('This is an error', $error->getMessage());
        $this->assertNull($error->getLine());
        $this->assertNull($error->getColumn());
        $this->assertNull($error->getSource());
    }

    public function testAddsAttachmentIsDelegated(): void
    {
        $buffer = new ToolReportBuffer('tool-name');
        $report = new ToolReport('tool-name', $buffer, sys_get_temp_dir());

        $report->addAttachment('/some/file', 'local');

        $attachments = $buffer->getAttachments();

        $this->assertCount(1, $attachments);
        $this->arrayHasKey(0);
        $attachment = $attachments[0];
        $this->assertInstanceOf(AttachmentBuffer::class, $attachment);
        $this->assertSame('/some/file', $attachment->getAbsolutePath());
        $this->assertSame('local', $attachment->getLocalName());
    }

    public function testAddsAttachmentIsDelegatedWhenCalledWithoutNameOverride(): void
    {
        $buffer = new ToolReportBuffer('tool-name');
        $report = new ToolReport('tool-name', $buffer, sys_get_temp_dir());

        $report->addAttachment('/some/file');

        $attachments = $buffer->getAttachments();

        $this->assertCount(1, $attachments);
        $this->arrayHasKey(0);
        $attachment = $attachments[0];
        $this->assertInstanceOf(AttachmentBuffer::class, $attachment);
        $this->assertSame('/some/file', $attachment->getAbsolutePath());
        $this->assertSame('file', $attachment->getLocalName());
    }

    public function testAddsBufferAsAttachment(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();

        $buffer = new ToolReportBuffer('tool-name');
        $report = new ToolReport('tool-name', $buffer, '/path/to/temp/directory', $filesystem);

        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->willReturnCallback(function (string $filePath, string $data) {
                $this->assertStringStartsWith('/path/to/temp/directory/local-name', $filePath);
                $this->assertSame('file contents', $data);
            });

        $report->addBufferAsAttachment('file contents', 'local-name');

        $attachments = $buffer->getAttachments();

        $this->assertCount(1, $attachments);
        $this->arrayHasKey(0);
        $attachment = $attachments[0];
        $this->assertInstanceOf(AttachmentBuffer::class, $attachment);
        $this->assertStringStartsWith('/path/to/temp/directory/local-name', $attachment->getAbsolutePath());
        $this->assertSame('local-name', $attachment->getLocalName());
    }

    public function testFinishSetsTheStatus(): void
    {
        $buffer = new ToolReportBuffer('tool-name');
        $report = new ToolReport('tool-name', $buffer, sys_get_temp_dir());

        $report->finish('passed');

        $this->assertSame('passed', $buffer->getStatus());
    }
}
