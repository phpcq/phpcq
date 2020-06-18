<?php

declare(strict_types=1);

namespace Phpcq\Test\Report;

use Phpcq\Report\Buffer\AttachmentBuffer;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\DiffBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use Phpcq\Report\ToolReport;
use Phpcq\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/** @covers \Phpcq\Report\ToolReport */
class ToolReportTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testCanBeInstantiated(): void
    {
        new ToolReport(
            new ToolReportBuffer('tool-name', 'report-name', '1.0.0'),
            self::$tempdir
        );
        $this->expectNotToPerformAssertions();
    }

    public function testAddsDiagnostic(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $report = new ToolReport($buffer, self::$tempdir);

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
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $report = new ToolReport($buffer, self::$tempdir);

        $report->addDiagnostic('error', 'This is an error');

        $report->close(ToolReport::STATUS_PASSED);

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
        $buffer     = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $report     = new ToolReport($buffer, self::$tempdir, $filesystem);

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
        $buffer     = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $report     = new ToolReport($buffer, '/our/temp/dir', $filesystem);

        // "forgotten" end calls on file builders.
        $report->addAttachment('foo')->fromFile('/some/dir/file.foo')->setMimeType('application/foo');
        $report->addAttachment('bar')->fromFile('/some/dir/file.bar');

        $report->close(ToolReport::STATUS_PASSED);

        $this->assertEquals(
            [
                new AttachmentBuffer('/some/dir/file.foo', 'foo', 'application/foo'),
                new AttachmentBuffer('/some/dir/file.bar', 'bar', null),
            ],
            $buffer->getAttachments()
        );
    }

    public function testAddsDiff(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();
        $buffer     = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $report     = new ToolReport($buffer, self::$tempdir, $filesystem);

        $this->assertSame($report, $report->addDiff('local')->fromFile('/some/patch-file.diff')->end());

        $attachments = $buffer->getDiffs();

        $this->assertCount(1, $attachments);
        $this->arrayHasKey(0);
        $attachment = $attachments[0];
        $this->assertInstanceOf(DiffBuffer::class, $attachment);
        $this->assertSame('/some/patch-file.diff', $attachment->getAbsolutePath());
        $this->assertSame('local', $attachment->getLocalName());
    }

    public function testEndIsCalledForPendingDiffBuilderFromFinish(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->getMock();
        $buffer     = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $report     = new ToolReport($buffer, '/our/temp/dir', $filesystem);

        // "forgotten" end calls on file builders.
        $report->addDiff('foo')->fromFile('/some/dir/file.diff');
        $report->addDiff('bar')->fromFile('/some/dir/file.diff');

        $report->close(ToolReport::STATUS_PASSED);

        $this->assertEquals(
            [
                new DiffBuffer('/some/dir/file.diff', 'foo'),
                new DiffBuffer('/some/dir/file.diff', 'bar'),
            ],
            $buffer->getDiffs()
        );
    }

    public function testFinishSetsTheStatus(): void
    {
        $buffer = new ToolReportBuffer('tool-name', 'report-name', '1.0.0');
        $report = new ToolReport($buffer, self::$tempdir);

        $report->close('passed');

        $this->assertSame('passed', $buffer->getStatus());
    }
}
