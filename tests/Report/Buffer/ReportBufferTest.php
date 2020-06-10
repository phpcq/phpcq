<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use DateTimeImmutable;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Buffer\ToolReportBuffer;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Buffer\ReportBuffer */
class ReportBufferTest extends TestCase
{
    public function testConstructionCreatesEmpty(): void
    {
        $buffer = new ReportBuffer();
        $this->assertSame('started', $buffer->getStatus());
        $this->assertEqualsWithDelta(new DateTimeImmutable(), $buffer->getStartedAt(), 1);
        $this->assertNull($buffer->getCompletedAt());
        $this->assertSame([], $buffer->getToolReports());
    }

    public function testCallingCompleteClosesReport(): void
    {
        $buffer = new ReportBuffer();

        $buffer->complete('passed');

        $this->assertSame('passed', $buffer->getStatus());
        $this->assertEqualsWithDelta(new DateTimeImmutable(), $buffer->getCompletedAt(), 1);
    }

    public function testCreatesToolReport(): void
    {
        $buffer = new ReportBuffer();

        $toolBuffer = $buffer->createToolReport('tool-name');

        $this->assertInstanceOf(ToolReportBuffer::class, $toolBuffer);
        $this->assertSame('tool-name', $toolBuffer->getToolName());
        $this->assertSame([$toolBuffer], $buffer->getToolReports());
    }

    public function testCreatesToolReportWithIncrementedNameWhenToolAlreadyExists(): void
    {
        $buffer = new ReportBuffer();

        $toolBuffer1 = $buffer->createToolReport('tool-name');
        $toolBuffer2 = $buffer->createToolReport('tool-name');

        $this->assertInstanceOf(ToolReportBuffer::class, $toolBuffer1);
        $this->assertInstanceOf(ToolReportBuffer::class, $toolBuffer2);
        $this->assertSame('tool-name', $toolBuffer1->getReportName());
        $this->assertSame('tool-name-1', $toolBuffer2->getReportName());
        $this->assertSame([$toolBuffer1, $toolBuffer2], $buffer->getToolReports());
    }

    public function testCountDiagnosticsGroupedBySeverity(): void
    {
        $buffer = new ReportBuffer();
        $toolBuffer = $buffer->createToolReport('tool-name');
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Info 1', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Info 2', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_NOTICE, 'Notice 1', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_ERROR, 'Error 1', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_ERROR, 'Error 2', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_ERROR, 'Error 3', null, null, null, null, null),
        );

        $toolBuffer2 = $buffer->createToolReport('tool2-name');
        $toolBuffer2->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Tool 2 Info 1', null, null, null, null, null),
        );
        $toolBuffer2->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Tool 2 Info 2', null, null, null, null, null),
        );

        $this->assertEquals(
            [
                ToolReportInterface::SEVERITY_ERROR => 3,
                ToolReportInterface::SEVERITY_WARNING => 0,
                ToolReportInterface::SEVERITY_NOTICE => 1,
                ToolReportInterface::SEVERITY_INFO => 4,
            ],
            $buffer->countDiagnosticsGroupedBySeverity()
        );
    }
}
