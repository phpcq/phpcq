<?php

declare(strict_types=1);

namespace Phpcq\Test\Task;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\ToolReportInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingParallelTaskInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingTaskInterface;
use Phpcq\Report\Buffer\ReportBuffer;
use Phpcq\Report\Report;
use Phpcq\Runner\Repository\RepositoryInterface;
use Phpcq\Task\TasklistInterface;
use Phpcq\Task\TaskScheduler;
use Phpcq\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;
use Throwable;

/** @covers \Phpcq\Task\TaskScheduler */
class TaskSchedulerTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testCanRunEmptyList(): void
    {
        $output = $this->getMockForAbstractClass(OutputInterface::class);

        // Dummy report - not used but can not mock due to lack of interface.
        $report = new Report(
            new ReportBuffer(),
            $this->getMockForAbstractClass(RepositoryInterface::class),
            self::$tempdir
        );

        $list = $this->getMockForAbstractClass(TasklistInterface::class);
        $generator = function () {
            yield from [];
        };
        $list->expects($this->once())->method('getIterator')->willReturn($generator());

        $scheduler = new TaskScheduler($list, 1, $report, $output, false);
        $this->assertTrue($scheduler->run());
    }

    public function testCanNotBeRunTwice(): void
    {
        $output = $this->getMockForAbstractClass(OutputInterface::class);

        // Dummy report - not used but can not mock due to lack of interface.
        $report = new Report(
            new ReportBuffer(),
            $this->getMockForAbstractClass(RepositoryInterface::class),
            self::$tempdir
        );

        $list = $this->getMockForAbstractClass(TasklistInterface::class);
        $generator = function () {
            yield from [];
        };
        $list->expects($this->once())->method('getIterator')->willReturn($generator());

        $scheduler = new TaskScheduler($list, 1, $report, $output, false);
        $scheduler->run();
        $this->expectException(\Phpcq\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Can not run twice.');
        $scheduler->run();
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public function taskListProvider(): array
    {
        return [
            'run non parallel tasks sequentially even if 4 threads available' => [
                'expected' => [
                    $this->start('tool-1'),
                    $this->end('tool-1'),
                    $this->start('tool-2'),
                    $this->end('tool-2'),
                    $this->start('tool-3'),
                    $this->end('tool-3'),
                    $this->start('tool-4'),
                    $this->end('tool-4'),
                ],
                'threads' => 4,
                'tasks' => [
                    $this->succeedingTask('tool-1'),
                    $this->succeedingTask('tool-2'),
                    $this->succeedingTask('tool-3'),
                    $this->succeedingTask('tool-4'),
                ],
            ],
            'run parallel tasks parallel in 4 threads' => [
                'expected' => [
                    $this->start('tool-1'),
                    $this->start('tool-2'),
                    $this->start('tool-3'),
                    $this->start('tool-4'),
                    $this->end('tool-1'),
                    $this->end('tool-2'),
                    $this->end('tool-3'),
                    $this->end('tool-4'),
                ],
                'threads' => 4,
                'tasks' => [
                    $this->succeedingParallelTask(1, 'tool-1'),
                    $this->succeedingParallelTask(2, 'tool-2'),
                    $this->succeedingParallelTask(3, 'tool-3'),
                    $this->succeedingParallelTask(4, 'tool-4'),
                ],
            ],
            'run parallel tasks parallel in 2 threads' => [
                'expected' => [
                    $this->start('tool-1'),
                    $this->start('tool-2'),
                    $this->end('tool-1'),
                    $this->start('tool-3'),
                    $this->end('tool-2'),
                    $this->start('tool-4'),
                    $this->end('tool-3'),
                    $this->end('tool-4'),
                ],
                'threads' => 2,
                'tasks' => [
                    $this->succeedingParallelTask(2, 'tool-1'),
                    $this->succeedingParallelTask(3, 'tool-2'),
                    $this->succeedingParallelTask(3, 'tool-3'),
                    $this->succeedingParallelTask(4, 'tool-4'),
                ],
            ],
            'run parallel tasks parallel in 2 threads, filling up the from pending tasks after one finished' => [
                'expected' => [
                    $this->start('tool-1'),
                    $this->start('tool-2'),
                    $this->end('tool-1'),
                    $this->start('tool-3'),
                    $this->end('tool-2'),
                    $this->start('tool-4'),
                    $this->end('tool-3'),
                    $this->end('tool-4'),
                ],
                'threads' => 2,
                'tasks' => [
                    $this->succeedingParallelTask(1, 'tool-1'),
                    $this->succeedingParallelTask(2, 'tool-2'),
                    $this->succeedingParallelTask(2, 'tool-3'),
                    $this->succeedingParallelTask(2, 'tool-4'),
                ],
            ],
            'run parallel tasks parallel in 2 threads, blocking for non parallelizable task' => [
                'expected' => [
                    $this->start('tool-1'),
                    $this->start('tool-2'),
                    $this->end('tool-1'),
                    $this->end('tool-2'),
                    $this->start('tool-3'),
                    $this->end('tool-3'),
                    $this->start('tool-4'),
                    $this->end('tool-4'),
                ],
                'threads' => 2,
                'tasks' => [
                    $this->succeedingParallelTask(1, 'tool-1'),
                    $this->succeedingParallelTask(2, 'tool-2'),
                    $this->mockTask('tool-3', ReportInterface::STATUS_PASSED),
                    $this->succeedingParallelTask(2, 'tool-4'),
                ],
            ],
            'run parallel tasks parallel in 4 threads depending on costs' => [
                'expected' => [
                    $this->start('tool-1'),
                    $this->start('tool-2'),
                    $this->end('tool-1'),
                    $this->end('tool-2'),
                    $this->start('tool-3'),
                    $this->start('tool-4'),
                    $this->end('tool-3'),
                    $this->end('tool-4'),
                ],
                'threads' => 4,
                'tasks' => [
                    $this->succeedingParallelTask(1, 'tool-1', 1),
                    $this->succeedingParallelTask(2, 'tool-2', 2),
                    $this->succeedingParallelTask(3, 'tool-3', 3),
                    $this->succeedingParallelTask(4, 'tool-4', 1),
                ],
            ],
            'run mixed task types in 4 threads depending on costs' => [
                'expected' => [
                    $this->start('tool-1'),
                    $this->start('tool-2'),
                    $this->end('tool-1'),
                    $this->end('tool-2'),
                    $this->start('tool-3'),
                    $this->end('tool-3'),
                    $this->start('blocker'),
                    $this->end('blocker'),
                    $this->start('tool-4'),
                    $this->end('tool-4'),
                ],
                'threads' => 4,
                'tasks' => [
                    $this->succeedingParallelTask(1, 'tool-1', 1),
                    $this->succeedingParallelTask(2, 'tool-2', 2),
                    $this->succeedingParallelTask(3, 'tool-3', 3),
                    $this->succeedingTask('blocker'),
                    $this->succeedingParallelTask(4, 'tool-4', 1),
                ],
            ],
        ];
    }

    /** @dataProvider taskListProvider */
    public function testRunsTasks(array $expected, int $threads, array $tasks): void
    {
        $output = $this->getMockForAbstractClass(OutputInterface::class);
        $result = [];
        $output
            ->expects($this->exactly(count($expected)))
            ->method('writeln')
            ->willReturnCallback(function (string $message) use (&$result) {
                $result[] = $message;
            });

        // Dummy report - not used but can not mock due to lack of interface.
        $report = new Report(
            new ReportBuffer(),
            $this->getMockForAbstractClass(RepositoryInterface::class),
            self::$tempdir
        );

        $list = $this->getMockForAbstractClass(TasklistInterface::class);
        $generator = function () use ($tasks) {
            foreach ($tasks as $task) {
                yield $task;
            }
        };
        $list->expects($this->once())->method('getIterator')->willReturn($generator());

        $scheduler = new TaskScheduler($list, $threads, $report, $output, false);
        $scheduler->run();
        $this->assertSame($expected, $result, 'Execution steps did not match.');
    }

    public function fastFinishProvider(): array
    {
        return [
            'continues on failure if fast finish is not enabled' => [
                'expected' => [
                    'success-1' => ReportInterface::STATUS_PASSED,
                    'failure-1' => ReportInterface::STATUS_FAILED,
                    'success-2' => ReportInterface::STATUS_PASSED,
                    'failure-2' => ReportInterface::STATUS_FAILED,
                    'success-3' => ReportInterface::STATUS_PASSED,
                ],
                'fastFinish' => false,
                'tasks' => [
                    $this->succeedingParallelTask(8, 'success-1'),
                    $this->failingParallelTask(4, 'failure-1'),
                    $this->succeedingParallelTask(1, 'success-2'),
                    $this->failingTask('failure-2'),
                    $this->succeedingParallelTask(1, 'success-3'),
                ]
            ],
            'stops on failure of parallel task if fast finish is enabled' => [
                'expected' => [
                    'success-1' => ReportInterface::STATUS_PASSED,
                    'failure-1' => ReportInterface::STATUS_FAILED,
                ],
                'fastFinish' => true,
                'tasks' => [
                    $this->succeedingParallelTask(8, 'success-1'),
                    $this->failingParallelTask(4, 'failure-1'),
                    $this->skippedParallelTask(1),
                    $this->skippedParallelTask(1),
                ]
            ],
            'stops on failure of single run task if fast finish is enabled' => [
                'expected' => [
                    'success-1' => ReportInterface::STATUS_PASSED,
                    'failure-1' => ReportInterface::STATUS_FAILED,
                ],
                'fastFinish' => true,
                'tasks' => [
                    $this->succeedingParallelTask(8, 'success-1'),
                    $this->failingTask('failure-1'),
                    $this->skippedParallelTask(1),
                    $this->skippedParallelTask(1),
                ]
            ],
            'continues on exception if fast finish is not enabled' => [
                'expected' => [
                    'success-1' => ReportInterface::STATUS_PASSED,
                    'failure-1' => ReportInterface::STATUS_FAILED,
                    'success-2' => ReportInterface::STATUS_PASSED,
                    'failure-2' => ReportInterface::STATUS_FAILED,
                    'success-3' => ReportInterface::STATUS_PASSED,
                ],
                'fastFinish' => false,
                'tasks' => [
                    $this->succeedingParallelTask(8, 'success-1'),
                    $this->throwingParallelTask(4, 'failure-1'),
                    $this->succeedingParallelTask(1, 'success-2'),
                    $this->throwingTask('failure-2'),
                    $this->succeedingParallelTask(1, 'success-3'),
                ]
            ],
            'stops on exception of parallel task if fast finish is enabled' => [
                'expected' => [
                    'success-1' => ReportInterface::STATUS_PASSED,
                    'failure-1' => ReportInterface::STATUS_FAILED,
                ],
                'fastFinish' => true,
                'tasks' => [
                    $this->succeedingParallelTask(8, 'success-1'),
                    $this->throwingParallelTask(4, 'failure-1'),
                    $this->skippedParallelTask(1),
                    $this->skippedParallelTask(1),
                ]
            ],
            'stops on exception of single run task if fast finish is enabled' => [
                'expected' => [
                    'success-1' => ReportInterface::STATUS_PASSED,
                    'failure-1' => ReportInterface::STATUS_FAILED,
                ],
                'fastFinish' => true,
                'tasks' => [
                    $this->succeedingParallelTask(8, 'success-1'),
                    $this->throwingTask('failure-1'),
                    $this->skippedParallelTask(1),
                    $this->skippedParallelTask(1),
                ]
            ],
        ];
    }

    /** @dataProvider fastFinishProvider */
    public function testFastFinishWorksAsExpected(array $expected, bool $fastFinish, array $tasks): void
    {
        $output = $this->getMockForAbstractClass(OutputInterface::class);
        $report = new Report(
            $buffer = new ReportBuffer(),
            $this->getMockForAbstractClass(RepositoryInterface::class),
            self::$tempdir
        );

        $list = $this->getMockForAbstractClass(TasklistInterface::class);
        $generator = function () use ($tasks) {
            foreach ($tasks as $task) {
                yield $task;
            }
        };
        $list->expects($this->once())->method('getIterator')->willReturn($generator());

        $scheduler = new TaskScheduler($list, 2, $report, $output, $fastFinish);
        $scheduler->run();

        $result = [];
        foreach ($buffer->getToolReports() as $report) {
            $result[$report->getToolName()] = $report->getStatus();
        }

        $this->assertSame($expected, $result);
    }

    private function start(string $toolName): string
    {
        return $toolName . ' starting';
    }

    private function end(string $toolName): string
    {
        return $toolName . ' finished';
    }

    private function succeedingTask(string $toolName): ReportWritingTaskInterface
    {
        return $this->mockTask($toolName, ReportInterface::STATUS_PASSED);
    }

    private function failingTask(string $toolName): ReportWritingTaskInterface
    {
        return $this->mockTask($toolName, ReportInterface::STATUS_FAILED);
    }

    private function throwingTask(string $toolName): ReportWritingTaskInterface
    {
        return $this->mockTask($toolName, new RuntimeException('fail miserably'));
    }

    /** @param string|Throwable|null $result */
    private function mockTask(?string $toolName, $result): ReportWritingTaskInterface
    {
        $mock = $this->getMockForAbstractClass(ReportWritingTaskInterface::class);
        $mock->method('getToolName')->willReturn($toolName);
        // Should be skipped.
        if (null === $result) {
            $mock->expects($this->never())->method('runWithReport');
            return $mock;
        }

        $mock
            ->method('runWithReport')
            ->willReturnCallback(function (ToolReportInterface $report) use ($result) {
                if ($result instanceof Throwable) {
                    $report->close(ReportInterface::STATUS_FAILED);
                    throw $result;
                }
                $report->close($result);
            });

        return $mock;
    }

    private function succeedingParallelTask(
        int $tickDuration,
        string $toolName,
        int $cost = 1
    ): ReportWritingParallelTaskInterface {
        return $this->mockParallelizableTask($tickDuration, $cost, $toolName, ReportInterface::STATUS_PASSED);
    }

    private function failingParallelTask(
        int $tickDuration,
        string $toolName,
        int $cost = 1
    ): ReportWritingParallelTaskInterface {
        return $this->mockParallelizableTask($tickDuration, $cost, $toolName, ReportInterface::STATUS_FAILED);
    }

    private function throwingParallelTask(
        int $tickDuration,
        string $toolName,
        int $cost = 1
    ): ReportWritingParallelTaskInterface {
        return $this->mockParallelizableTask($tickDuration, $cost, $toolName, new RuntimeException('fail miserably'));
    }

    private function skippedParallelTask(int $tickDuration): ReportWritingParallelTaskInterface
    {
        return $this->mockParallelizableTask($tickDuration, 1, uniqid('skipped-'), null);
    }

    /** @param string|Throwable|null $result */
    private function mockParallelizableTask(
        int $tickDuration,
        int $cost,
        string $toolName,
        $result
    ): ReportWritingParallelTaskInterface {
        $mock = $this->getMockForAbstractClass(ReportWritingParallelTaskInterface::class);
        $mock->method('getToolName')->willReturn($toolName);

        // Should be skipped.
        if (null === $result) {
            $mock->expects($this->never())->method('runWithReport');
            $mock->expects($this->never())->method('tick');
            $mock->expects($this->never())->method('getCost');
            return $mock;
        }

        $tickResults   = array_fill(0, $tickDuration - 1, true);
        $tickResults[] = false;

        $taskReport = null;
        $mock
            ->method('runWithReport')
            ->willReturnCallback(function (ToolReportInterface $report) use ($result, &$taskReport) {
                $taskReport = $report;
            });
        $mock
            ->expects($this->exactly($tickDuration))
            ->method('tick')
            ->willReturnCallback(function () use (&$tickResults, $result, &$taskReport) {
                if (1 === count($tickResults)) {
                    if ($result instanceof Throwable) {
                        $taskReport->close(ReportInterface::STATUS_FAILED);
                        throw $result;
                    }
                    $taskReport->close($result);
                }

                return array_shift($tickResults);
            });
        $mock->expects($this->atLeastOnce())->method('getCost')->willReturn($cost);

        return $mock;
    }
}
