<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Report\Buffer;

use DateTimeImmutable;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\DiagnosticBuffer;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\Buffer\TaskReportBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Report\Buffer\ReportBuffer */
final class ReportBufferTest extends TestCase
{
    public function testConstructionCreatesEmpty(): void
    {
        $buffer = new ReportBuffer();
        $this->assertSame('started', $buffer->getStatus());
        $this->assertEqualsWithDelta(new DateTimeImmutable(), $buffer->getStartedAt(), 1);
        $this->assertNull($buffer->getCompletedAt());
        $this->assertSame([], $buffer->getTaskReports());
    }

    public function testCallingCompleteClosesReport(): void
    {
        $buffer = new ReportBuffer();

        $buffer->complete('passed');

        $this->assertSame('passed', $buffer->getStatus());
        $this->assertEqualsWithDelta(new DateTimeImmutable(), $buffer->getCompletedAt(), 1);
    }

    public function testCreatesTaskReport(): void
    {
        $buffer = new ReportBuffer();

        $toolBuffer = $buffer->createTaskReport('task-name');

        $this->assertInstanceOf(TaskReportBuffer::class, $toolBuffer);
        $this->assertSame('task-name', $toolBuffer->getTaskName());
        $this->assertSame([$toolBuffer], $buffer->getTaskReports());
    }

    public function testCreatesTaskReportWithIncrementedNameWhenToolAlreadyExists(): void
    {
        $buffer = new ReportBuffer();

        $toolBuffer1 = $buffer->createTaskReport('task-name');
        $toolBuffer2 = $buffer->createTaskReport('task-name');

        $this->assertInstanceOf(TaskReportBuffer::class, $toolBuffer1);
        $this->assertInstanceOf(TaskReportBuffer::class, $toolBuffer2);
        $this->assertSame('task-name', $toolBuffer1->getReportName());
        $this->assertSame('task-name-1', $toolBuffer2->getReportName());
        $this->assertSame([$toolBuffer1, $toolBuffer2], $buffer->getTaskReports());
    }

    public function testCountDiagnosticsGroupedBySeverity(): void
    {
        $buffer = new ReportBuffer();
        $toolBuffer = $buffer->createTaskReport('task-name');
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_INFO, 'Info 1', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_INFO, 'Info 2', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_MARGINAL, 'Notice 1', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_MAJOR, 'Error 1', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_MAJOR, 'Error 2', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_MAJOR, 'Error 3', null, null, null, null, null),
        );

        $toolBuffer2 = $buffer->createTaskReport('task2-name');
        $toolBuffer2->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_INFO, 'Tool 2 Info 1', null, null, null, null, null),
        );
        $toolBuffer2->addDiagnostic(
            new DiagnosticBuffer(TaskReportInterface::SEVERITY_INFO, 'Tool 2 Info 2', null, null, null, null, null),
        );

        $this->assertEquals(
            [
                TaskReportInterface::SEVERITY_FATAL    => 0,
                TaskReportInterface::SEVERITY_MAJOR    => 3,
                TaskReportInterface::SEVERITY_MINOR    => 0,
                TaskReportInterface::SEVERITY_MARGINAL => 1,
                TaskReportInterface::SEVERITY_INFO     => 4,
                TaskReportInterface::SEVERITY_NONE     => 0,
            ],
            $buffer->countDiagnosticsGroupedBySeverity()
        );
    }
}
