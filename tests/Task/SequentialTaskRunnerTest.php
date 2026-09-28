<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Task;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingParallelTaskInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingTaskInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\Report;
use Phpcq\Runner\Task\SequentialTaskRunner;
use Phpcq\Runner\Task\Tasklist;
use Phpcq\Runner\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Task\SequentialTaskRunner */
final class SequentialTaskRunnerTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testRunsTasksInOrder(): void
    {
        $order = [];
        $list  = new Tasklist();
        $list->add($this->createTask('a', TaskReportInterface::STATUS_PASSED, $order));
        $list->add($this->createTask('b', TaskReportInterface::STATUS_PASSED, $order));

        self::assertTrue($this->createRunner(false)->run($list));
        self::assertSame(['a', 'b'], $order);
    }

    public function testRunsParallelTaskWithHighCostToCompletion(): void
    {
        $ticks = 0;
        $task  = $this->createMock(ReportWritingParallelTaskInterface::class);
        $task->method('getToolName')->willReturn('phpcbf');
        $task->method('getCost')->willReturn(8);
        $task->expects(self::once())->method('runWithReport')->willReturnCallback(
            function (TaskReportInterface $report) use (&$reportRef): void {
                $reportRef = $report;
            }
        );
        $task->method('tick')->willReturnCallback(function () use (&$ticks, &$reportRef): bool {
            if (++$ticks < 3) {
                return true;
            }
            $reportRef->close(TaskReportInterface::STATUS_PASSED);

            return false;
        });

        $list = new Tasklist();
        $list->add($task);

        self::assertTrue($this->createRunner(false)->run($list));
        self::assertSame(3, $ticks);
    }

    public function testContinuesAfterFailureWithoutFastFinish(): void
    {
        $order = [];
        $list  = new Tasklist();
        $list->add($this->createTask('a', TaskReportInterface::STATUS_FAILED, $order));
        $list->add($this->createTask('b', TaskReportInterface::STATUS_PASSED, $order));

        self::assertFalse($this->createRunner(false)->run($list));
        self::assertSame(['a', 'b'], $order);
    }

    public function testStopsAfterFailureWithFastFinish(): void
    {
        $order = [];
        $list  = new Tasklist();
        $list->add($this->createTask('a', TaskReportInterface::STATUS_FAILED, $order));
        $list->add($this->createTask('b', TaskReportInterface::STATUS_PASSED, $order));

        self::assertFalse($this->createRunner(true)->run($list));
        self::assertSame(['a'], $order);
    }

    public function testExceptionCountsAsFailure(): void
    {
        $order = [];
        $task  = $this->createMock(ReportWritingTaskInterface::class);
        $task->method('getToolName')->willReturn('broken');
        $task->method('runWithReport')->willThrowException(new RuntimeException('Process failed'));

        $list = new Tasklist();
        $list->add($task);
        $list->add($this->createTask('b', TaskReportInterface::STATUS_PASSED, $order));

        self::assertFalse($this->createRunner(false)->run($list));
        self::assertSame(['b'], $order);
    }

    public function testExceptionClosesStartedReportAsFailedWithFatalDiagnostic(): void
    {
        $task = $this->createMock(ReportWritingTaskInterface::class);
        $task->method('getToolName')->willReturn('broken');
        $task->method('runWithReport')->willThrowException(new RuntimeException('Process failed'));

        $list = new Tasklist();
        $list->add($task);

        $buffer = new ReportBuffer();
        $runner = new SequentialTaskRunner(
            new Report($buffer, self::$tempdir),
            $this->createMock(OutputInterface::class),
            false
        );

        self::assertFalse($runner->run($list));

        $taskReports = $buffer->getTaskReports();
        self::assertCount(1, $taskReports);
        $taskReport = $taskReports[0];
        self::assertSame(TaskReportInterface::STATUS_FAILED, $taskReport->getStatus());
        $diagnostics = iterator_to_array($taskReport->getDiagnostics(), false);
        self::assertCount(1, $diagnostics);
        self::assertSame(TaskReportInterface::SEVERITY_FATAL, $diagnostics[0]->getSeverity());
        self::assertSame('Task "broken" failed: Process failed', $diagnostics[0]->getMessage());
    }

    public function testExceptionKeepsAlreadyClosedReport(): void
    {
        $task = $this->createMock(ReportWritingTaskInterface::class);
        $task->method('getToolName')->willReturn('broken');
        $task->method('runWithReport')->willReturnCallback(
            static function (TaskReportInterface $report): void {
                $report->close(TaskReportInterface::STATUS_FAILED);
                throw new RuntimeException('Process failed');
            }
        );

        $list = new Tasklist();
        $list->add($task);

        $buffer = new ReportBuffer();
        $runner = new SequentialTaskRunner(
            new Report($buffer, self::$tempdir),
            $this->createMock(OutputInterface::class),
            false
        );

        self::assertFalse($runner->run($list));

        $taskReports = $buffer->getTaskReports();
        self::assertCount(1, $taskReports);
        self::assertSame(TaskReportInterface::STATUS_FAILED, $taskReports[0]->getStatus());
        self::assertCount(0, iterator_to_array($taskReports[0]->getDiagnostics(), false));
    }

    private function createRunner(bool $fastFinish): SequentialTaskRunner
    {
        return new SequentialTaskRunner(
            new Report(new ReportBuffer(), self::$tempdir),
            $this->createMock(OutputInterface::class),
            $fastFinish
        );
    }

    /** @param list<string> $order */
    private function createTask(string $name, string $status, array &$order): ReportWritingTaskInterface
    {
        $task = $this->createMock(ReportWritingTaskInterface::class);
        $task->method('getToolName')->willReturn($name);
        $task->method('runWithReport')->willReturnCallback(
            function (TaskReportInterface $report) use ($name, $status, &$order): void {
                $order[] = $name;
                $report->close($status);
            }
        );

        return $task;
    }
}
