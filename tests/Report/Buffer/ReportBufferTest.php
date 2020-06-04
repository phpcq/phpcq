<?php

declare(strict_types=1);

namespace Phpcq\Test\Report\Buffer;

use DateTimeImmutable;
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
}
