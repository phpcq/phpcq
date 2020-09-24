<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Report\Buffer;

use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\AttachmentBuffer;
use Phpcq\Runner\Report\Buffer\DiagnosticBuffer;
use Phpcq\Runner\Report\Buffer\DiffBuffer;
use Phpcq\Runner\Report\Buffer\TaskReportBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Report\Buffer\TaskReportBuffer */
class TaskReportBufferTest extends TestCase
{
    public function testConstructionCreatesEmpty(): void
    {
        $buffer = new TaskReportBuffer('task-name', 'report-name');
        $this->assertSame('report-name', $buffer->getReportName());
        $this->assertSame('task-name', $buffer->getTaskName());
        $this->assertSame('started', $buffer->getStatus());
        $this->assertSame([], iterator_to_array($buffer->getDiagnostics()));
        $this->assertSame([], $buffer->getAttachments());
    }

    public function testSetsStatusToPassed(): void
    {
        $buffer = new TaskReportBuffer('task-name', 'report-name');

        $buffer->setStatus('passed');

        $this->assertSame('passed', $buffer->getStatus());
    }

    public function testSetsStatusToFailed(): void
    {
        $buffer = new TaskReportBuffer('task-name', 'report-name');

        $buffer->setStatus('failed');

        $this->assertSame('failed', $buffer->getStatus());
    }

    public function testDoesNotSetStatusToPassedWhenAlreadyFailed(): void
    {
        $buffer = new TaskReportBuffer('task-name', 'report-name');

        $buffer->setStatus('failed');
        $buffer->setStatus('passed');

        $this->assertSame('failed', $buffer->getStatus());
    }

    public function testAddsDiagnostic(): void
    {
        $buffer = new TaskReportBuffer('task-name', 'report-name');
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
        $buffer = new TaskReportBuffer('task-name', 'report-name');
        $buffer->addAttachment($attachment = new AttachmentBuffer('/some/file', 'local', null));

        $attachments = $buffer->getAttachments();
        $this->assertCount(1, $attachments);
        $this->arrayHasKey(0);
        $this->assertSame($attachment, $attachments[0]);
    }

    public function testAddsDiff(): void
    {
        $buffer = new TaskReportBuffer('task-name', 'report-name');
        $buffer->addDiff($diff = new DiffBuffer('/some/file', 'local'));

        $diffs = $buffer->getDiffs();
        $this->assertCount(1, $diffs);
        $this->arrayHasKey(0);
        $this->assertSame($diff, $diffs[0]);
    }

    public function testCountDiagnosticsGroupedBySeverity(): void
    {
        $buffer = new TaskReportBuffer('task-name', 'report-name');
        $buffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_INFO, 'Info 1', null, null, null, null, null),
        );
        $buffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_INFO, 'Info 2', null, null, null, null, null),
        );
        $buffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_MARGINAL, 'Notice 1', null, null, null, null, null),
        );
        $buffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_MAJOR, 'Error 1', null, null, null, null, null),
        );
        $buffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_MAJOR, 'Error 2', null, null, null, null, null),
        );
        $buffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_MAJOR, 'Error 3', null, null, null, null, null),
        );

        $this->assertEquals(
            [
                TaskReportInterface::SEVERITY_FATAL    => 0,
                TaskReportInterface::SEVERITY_MAJOR    => 3,
                TaskReportInterface::SEVERITY_MINOR    => 0,
                TaskReportInterface::SEVERITY_MARGINAL => 1,
                TaskReportInterface::SEVERITY_INFO     => 2,
                TaskReportInterface::SEVERITY_NONE     => 0,
            ],
            $buffer->countDiagnosticsGroupedBySeverity()
        );
    }
}
