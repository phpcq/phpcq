<?php

declare(strict_types=1);

namespace Phpcq\Test\Report;

use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use Phpcq\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Report\Report */
class ReportTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testCanBeInstantiated(): void
    {
        new Report(new ReportBuffer(), self::$tempdir);
        $this->expectNotToPerformAssertions();
    }

    public function testAddTaskReportIsDelegated(): void
    {
        $buffer = new ReportBuffer();
        $report = new Report($buffer, self::$tempdir);

        $report->addTaskReport('task-name');

        $tools = $buffer->getTaskReports();
        $this->assertSame('task-name', $tools[0]->getTaskName());
    }
}
