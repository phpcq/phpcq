<?php

declare(strict_types=1);

namespace Phpcq\Test\Report;

use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use Phpcq\Runner\Repository\RepositoryInterface;
use Phpcq\Repository\ToolInformationInterface;
use Phpcq\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Report */
class ReportTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testCanBeInstantiated(): void
    {
        $installed = $this->getMockForAbstractClass(RepositoryInterface::class);
        new Report(new ReportBuffer(), $installed, self::$tempdir);
        $this->expectNotToPerformAssertions();
    }

    public function testAddToolReportIsDelegated(): void
    {
        $installed = $this->getMockForAbstractClass(RepositoryInterface::class);
        $buffer = new ReportBuffer();
        $report = new Report($buffer, $installed, self::$tempdir);

        $report->addToolReport('tool-name');

        $tools = $buffer->getToolReports();
        $this->assertSame('tool-name', $tools[0]->getToolName());
    }

    public function testSetsVersion(): void
    {
        $toolInformation = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $toolInformation
            ->expects($this->once())
            ->method('getPluginVersion')
            ->willReturn('1.0.0');

        $installed = $this->getMockForAbstractClass(RepositoryInterface::class);
        $installed
            ->expects($this->once())
            ->method('getPluginVersion')
            ->withAnyParameters()
            ->willReturn($toolInformation);

        $buffer = new ReportBuffer();
        $report = new Report($buffer, $installed, self::$tempdir);

        $report->addToolReport('tool-name');

        $tools = $buffer->getToolReports();
        $this->assertSame('1.0.0', $tools[0]->getToolVersion());
    }
}
