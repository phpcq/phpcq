<?php

declare(strict_types=1);

namespace Phpcq\Test\Report;

use Phpcq\Report\Buffer\AttachmentBuffer;
use Phpcq\Report\Buffer\DiagnosticBuffer;
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

    public function testAddsDiagnostic(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');
        $report = new ToolReport('tool-name', $buffer, sys_get_temp_dir());

        $this->assertSame(
            $report,
            $report->addDiagnostic('error', 'This is an error')->end()
        );

        $diagnostics = iterator_to_array($buffer->getDiagnostics());
        $this->assertCount(1, $diagnostics);
        /** @var DiagnosticBuffer $diagnostic */
        $diagnostic = $diagnostics[0];

        $this->assertSame('error', $diagnostic->getSeverity());
        $this->assertSame('This is an error', $diagnostic->getMessage());
    }

    public function testEndIsCalledForPendingDiagnosticBuilderFromFinish(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name');
        $report = new ToolReport('tool-name', $buffer, sys_get_temp_dir());

        $report->addDiagnostic('error', 'This is an error');

        $report->finish(ToolReport::STATUS_PASSED);

        $diagnostics = iterator_to_array($buffer->getDiagnostics());
        $this->assertCount(1, $diagnostics);
        /** @var DiagnosticBuffer $diagnostic */
        $diagnostic = $diagnostics[0];

        $this->assertSame('error', $diagnostic->getSeverity());
        $this->assertSame('This is an error', $diagnostic->getMessage());
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
