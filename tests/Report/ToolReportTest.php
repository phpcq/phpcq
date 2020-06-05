<?php

declare(strict_types=1);

namespace Phpcq\Test\Report;

use Phpcq\Report\Buffer\AttachmentBuffer;
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
            new ToolReportBuffer('tool-name', 'report-name'),
            sys_get_temp_dir()
        );
        $this->expectNotToPerformAssertions();
    }

    public function testAddErrorIsDelegated(): void
    {
        $this->markTestSkipped();
        /*
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
        /** @var SourceFileDiagnostic $error * /
        $error = $errors[0];

        $this->assertSame('error', $error->getSeverity());
        $this->assertSame('This is an error', $error->getMessage());
        $this->assertSame(10, $error->getLine());
        $this->assertSame(20, $error->getColumn());
        $this->assertSame('source name', $error->getSource());
        */
    }

    public function testAddErrorIsDelegatedWhenCalledWithNulledValues(): void
    {
        $this->markTestSkipped();
        /*
        $buffer = new ToolReportBuffer('tool-name');
        $report = new ToolReport('tool-name', $buffer, sys_get_temp_dir());

        $report->addDiagnostic('error', 'This is an error');

        $errors = iterator_to_array($buffer->getFile(ToolReport::UNKNOWN_FILE));
        $this->assertCount(1, $errors);
        /** @var SourceFileDiagnostic $error * /
        $error = $errors[0];

        $this->assertSame('error', $error->getSeverity());
        $this->assertSame('This is an error', $error->getMessage());
        $this->assertNull($error->getLine());
        $this->assertNull($error->getColumn());
        $this->assertNull($error->getSource());
        */
    }
    public function testAddsAttachment(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();
        $buffer     = new ToolReportBuffer('tool-name', 'report-name');
        $report     = new ToolReport('tool-name', $buffer, sys_get_temp_dir(), $filesystem);

        $this->assertSame($report, $report->addAttachment('local')->fromFile('/some/file')->end());

        $attachments = $buffer->getAttachments();

        $this->assertCount(1, $attachments);
        $this->arrayHasKey(0);
        $attachment = $attachments[0];
        $this->assertInstanceOf(AttachmentBuffer::class, $attachment);
        $this->assertSame('/some/file', $attachment->getAbsolutePath());
        $this->assertSame('local', $attachment->getLocalName());
        $this->assertNull($attachment->getMimeType());
    }

    public function testEndIsCalledForPendingAttachmentBuilderFromFinish(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();
        $buffer     = new ToolReportBuffer('tool-name', 'report-name');
        $report     = new ToolReport('tool-name', $buffer, '/our/temp/dir', $filesystem);

        // "forgotten" end calls on file builders.
        $report->addAttachment('foo')->fromFile('/some/dir/file.foo')->setMimeType('application/foo');
        $report->addAttachment('bar')->fromFile('/some/dir/file.bar');

        $report->finish(ToolReport::STATUS_PASSED);

        $this->assertEquals(
            [
                new AttachmentBuffer('/some/dir/file.foo', 'foo', 'application/foo'),
                new AttachmentBuffer('/some/dir/file.bar', 'bar', null),
            ],
            $buffer->getAttachments()
        );
    }

    public function testFinishSetsTheStatus(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');
        $report = new ToolReport('tool-name', $buffer, sys_get_temp_dir());

        $report->finish('passed');

        $this->assertSame('passed', $buffer->getStatus());
    }
}
