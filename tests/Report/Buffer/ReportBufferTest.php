<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use DateTimeImmutable;
use Phpcq\PluginApi\Version10\Report\ToolReportInterface;
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

        $toolBuffer = $buffer->createToolReport('tool-name', '1.0.0');

        $this->assertInstanceOf(ToolReportBuffer::class, $toolBuffer);
        $this->assertSame('tool-name', $toolBuffer->getToolName());
        $this->assertSame([$toolBuffer], $buffer->getToolReports());
    }

    public function testCreatesToolReportWithIncrementedNameWhenToolAlreadyExists(): void
    {
        $buffer = new ReportBuffer();

        $toolBuffer1 = $buffer->createToolReport('tool-name', '1.0.0');
        $toolBuffer2 = $buffer->createToolReport('tool-name', '1.0.0');

        $this->assertInstanceOf(ToolReportBuffer::class, $toolBuffer1);
        $this->assertInstanceOf(ToolReportBuffer::class, $toolBuffer2);
        $this->assertSame('tool-name', $toolBuffer1->getReportName());
        $this->assertSame('tool-name-1', $toolBuffer2->getReportName());
        $this->assertSame([$toolBuffer1, $toolBuffer2], $buffer->getToolReports());
    }

    public function testCountDiagnosticsGroupedBySeverity(): void
    {
        $buffer = new ReportBuffer();
        $toolBuffer = $buffer->createToolReport('tool-name', '1.0.0');
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Info 1', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Info 2', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_MARGINAL, 'Notice 1', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_MAJOR, 'Error 1', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_MAJOR, 'Error 2', null, null, null, null, null),
        );
        $toolBuffer->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_MAJOR, 'Error 3', null, null, null, null, null),
        );

        $toolBuffer2 = $buffer->createToolReport('tool2-name', '2.0.0');
        $toolBuffer2->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Tool 2 Info 1', null, null, null, null, null),
        );
        $toolBuffer2->addDiagnostic(
            new DiagnosticBuffer(ToolReportInterface::SEVERITY_INFO, 'Tool 2 Info 2', null, null, null, null, null),
        );

        $this->assertEquals(
            [
                ToolReportInterface::SEVERITY_FATAL    => 0,
                ToolReportInterface::SEVERITY_MAJOR    => 3,
                ToolReportInterface::SEVERITY_MINOR    => 0,
                ToolReportInterface::SEVERITY_MARGINAL => 1,
                ToolReportInterface::SEVERITY_INFO     => 4,
                ToolReportInterface::SEVERITY_NONE     => 0,
            ],
            $buffer->countDiagnosticsGroupedBySeverity()
        );
    }
}
