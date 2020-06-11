<?php

declare(strict_types=1);

namespace Phpcq\Test\Report;

use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use Phpcq\Repository\RepositoryInterface;
use Phpcq\Repository\ToolInformationInterface;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;

/** @covers \Phpcq\Report\Report */
class ReportTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $installed = $this->getMockForAbstractClass(RepositoryInterface::class);
        new Report(new ReportBuffer(), $installed, sys_get_temp_dir());
        $this->expectNotToPerformAssertions();
    }

    public function testAddToolReportIsDelegated(): void
    {
        $installed = $this->getMockForAbstractClass(RepositoryInterface::class);
        $buffer = new ReportBuffer();
        $report = new Report($buffer, $installed, sys_get_temp_dir());

        $report->addToolReport('tool-name');

        $tools = $buffer->getToolReports();
        $this->assertSame('tool-name', $tools[0]->getToolName());
    }

    public function testSetsVersion(): void
    {
        $toolInformation = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $toolInformation
            ->expects($this->once())
            ->method('getVersion')
            ->willReturn('1.0.0');

        $installed = $this->getMockForAbstractClass(RepositoryInterface::class);
        $installed
            ->expects($this->once())
            ->method('getTool')
            ->withAnyParameters()
            ->willReturn($toolInformation);

        $buffer = new ReportBuffer();
        $report = new Report($buffer, $installed, sys_get_temp_dir());

        $report->addToolReport('tool-name');

        $tools = $buffer->getToolReports();
        $this->assertSame('1.0.0', $tools[0]->getToolVersion());
    }
}
