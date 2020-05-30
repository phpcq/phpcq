<?php

declare(strict_types=1);

namespace Phpcq\Test\Report;

use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Report */
class ReportTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        new Report(new ReportBuffer(), sys_get_temp_dir());
        $this->expectNotToPerformAssertions();
    }

    public function testAddToolReportIsDelegated(): void
    {
        $buffer = new ReportBuffer();
        $report = new Report($buffer, sys_get_temp_dir());

        $report->addToolReport('tool-name');

        $tools = $buffer->getToolReports();
        $this->assertSame('tool-name', $tools[0]->getToolName());
    }
}
