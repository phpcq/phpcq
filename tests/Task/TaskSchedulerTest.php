<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Task;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingParallelTaskInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingTaskInterface;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\Report;
use Phpcq\Runner\Task\TasklistInterface;
use Phpcq\Runner\Task\TaskScheduler;
use Phpcq\Runner\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

/** @covers \Phpcq\Runner\Task\TaskScheduler */
final class TaskSchedulerTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testCanRunEmptyList(): void
    {
        $output = $this->createMock(OutputInterface::class);

        // Dummy report - not used but can not mock due to lack of interface.
        $report = new Report(new ReportBuffer(), self::$tempdir);

        $list = $this->createMock(TasklistInterface::class);
        $generator = function () {
            yield from [];
        };
        $list->expects($this->once())->method('getIterator')->willReturn($generator());

        $scheduler = new TaskScheduler($list, 1, $report, $output, false);
        $this->assertTrue($scheduler->run());
    }

    public function testCanNotBeRunTwice(): void
    {
        $output = $this->createMock(OutputInterface::class);

        // Dummy report - not used but can not mock due to lack of interface.
        $report = new Report(new ReportBuffer(), self::$tempdir);

        $list = $this->createMock(TasklistInterface::class);
        $generator = function () {
            yield from [];
        };
        $list->expects($this->once())->method('getIterator')->willReturn($generator());

        $scheduler = new TaskScheduler($list, 1, $report, $output, false);
        $scheduler->run();
        $this->expectException(\Phpcq\Runner\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Can not run twice.');
        $scheduler->run();
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public static function taskListProvider(): array
    {
        return [
            'run non parallel tasks sequentially even if 4 threads available' => [
                'expected' => [
                    self::start('tool-1'),
                    self::end('tool-1'),
                    self::start('tool-2'),
                    self::end('tool-2'),
                    self::start('tool-3'),
                    self::end('tool-3'),
                    self::start('tool-4'),
                    self::end('tool-4'),
                ],
                'threads' => 4,
                'tasks' => static fn (self $testCase): array => [
                    $testCase->succeedingTask('tool-1'),
                    $testCase->succeedingTask('tool-2'),
                    $testCase->succeedingTask('tool-3'),
                    $testCase->succeedingTask('tool-4'),
                ],
            ],
            'run parallel tasks parallel in 4 threads' => [
                'expected' => [
                    self::start('tool-1'),
                    self::start('tool-2'),
                    self::start('tool-3'),
                    self::start('tool-4'),
                    self::end('tool-1'),
                    self::end('tool-2'),
                    self::end('tool-3'),
                    self::end('tool-4'),
                ],
                'threads' => 4,
                'tasks' => static fn (self $testCase): array => [
                    $testCase->succeedingParallelTask(1, 'tool-1'),
                    $testCase->succeedingParallelTask(2, 'tool-2'),
                    $testCase->succeedingParallelTask(3, 'tool-3'),
                    $testCase->succeedingParallelTask(4, 'tool-4'),
                ],
            ],
            'run parallel tasks parallel in 2 threads' => [
                'expected' => [
                    self::start('tool-1'),
                    self::start('tool-2'),
                    self::end('tool-1'),
                    self::start('tool-3'),
                    self::end('tool-2'),
                    self::start('tool-4'),
                    self::end('tool-3'),
                    self::end('tool-4'),
                ],
                'threads' => 2,
                'tasks' => static fn (self $testCase): array => [
                    $testCase->succeedingParallelTask(2, 'tool-1'),
                    $testCase->succeedingParallelTask(3, 'tool-2'),
                    $testCase->succeedingParallelTask(3, 'tool-3'),
                    $testCase->succeedingParallelTask(4, 'tool-4'),
                ],
            ],
            'run parallel tasks parallel in 2 threads, filling up the from pending tasks after one finished' => [
                'expected' => [
                    self::start('tool-1'),
                    self::start('tool-2'),
                    self::end('tool-1'),
                    self::start('tool-3'),
                    self::end('tool-2'),
                    self::start('tool-4'),
                    self::end('tool-3'),
                    self::end('tool-4'),
                ],
                'threads' => 2,
                'tasks' => static fn (self $testCase): array => [
                    $testCase->succeedingParallelTask(1, 'tool-1'),
                    $testCase->succeedingParallelTask(2, 'tool-2'),
                    $testCase->succeedingParallelTask(2, 'tool-3'),
                    $testCase->succeedingParallelTask(2, 'tool-4'),
                ],
            ],
            'run parallel tasks parallel in 2 threads, blocking for non parallelizable task' => [
                'expected' => [
                    self::start('tool-1'),
                    self::start('tool-2'),
                    self::end('tool-1'),
                    self::end('tool-2'),
                    self::start('tool-3'),
                    self::end('tool-3'),
                    self::start('tool-4'),
                    self::end('tool-4'),
                ],
                'threads' => 2,
                'tasks' => static fn (self $testCase): array => [
                    $testCase->succeedingParallelTask(1, 'tool-1'),
                    $testCase->succeedingParallelTask(2, 'tool-2'),
                    $testCase->mockTask('tool-3', ReportInterface::STATUS_PASSED),
                    $testCase->succeedingParallelTask(2, 'tool-4'),
                ],
            ],
            'run parallel tasks parallel in 4 threads depending on costs' => [
                'expected' => [
                    self::start('tool-1'),
                    self::start('tool-2'),
                    self::end('tool-1'),
                    self::end('tool-2'),
                    self::start('tool-3'),
                    self::start('tool-4'),
                    self::end('tool-3'),
                    self::end('tool-4'),
                ],
                'threads' => 4,
                'tasks' => static fn (self $testCase): array => [
                    $testCase->succeedingParallelTask(1, 'tool-1', 1),
                    $testCase->succeedingParallelTask(2, 'tool-2', 2),
                    $testCase->succeedingParallelTask(3, 'tool-3', 3),
                    $testCase->succeedingParallelTask(4, 'tool-4', 1),
                ],
            ],
            'run mixed task types in 4 threads depending on costs' => [
                'expected' => [
                    self::start('tool-1'),
                    self::start('tool-2'),
                    self::end('tool-1'),
                    self::end('tool-2'),
                    self::start('tool-3'),
                    self::end('tool-3'),
                    self::start('blocker'),
                    self::end('blocker'),
                    self::start('tool-4'),
                    self::end('tool-4'),
                ],
                'threads' => 4,
                'tasks' => static fn (self $testCase): array => [
                    $testCase->succeedingParallelTask(1, 'tool-1', 1),
                    $testCase->succeedingParallelTask(2, 'tool-2', 2),
                    $testCase->succeedingParallelTask(3, 'tool-3', 3),
                    $testCase->succeedingTask('blocker'),
                    $testCase->succeedingParallelTask(4, 'tool-4', 1),
                ],
            ],
        ];
    }

    #[DataProvider('taskListProvider')]
    public function testRunsTasks(array $expected, int $threads, callable $tasks): void
    {
        $output = $this->createMock(OutputInterface::class);
        $result = [];
        $output
            ->expects($this->exactly(count($expected)))
            ->method('writeln')
            ->willReturnCallback(function (string $message) use (&$result) {
                $result[] = $message;
            });

        // Dummy report - not used but can not mock due to lack of interface.
        $report = new Report(new ReportBuffer(), self::$tempdir);

        $list = $this->createMock(TasklistInterface::class);
        $generator = function () use ($tasks) {
            foreach ($tasks($this) as $task) {
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
                'tasks' => static fn (self $testCase): array => [
                    $testCase->succeedingParallelTask(8, 'success-1'),
                    $testCase->throwingTask('failure-1'),
                    $testCase->skippedParallelTask(1),
                    $testCase->skippedParallelTask(1),
                ]
            ],
        ];
    }

    #[DataProvider('fastFinishProvider')]
    public function testFastFinishWorksAsExpected(array $expected, bool $fastFinish, callable $tasks): void
    {
        $output = $this->createMock(OutputInterface::class);
        $report = new Report($buffer = new ReportBuffer(), self::$tempdir);

        $list = $this->createMock(TasklistInterface::class);
        $generator = function () use ($tasks) {
            foreach ($tasks($this) as $task) {
                yield $task;
            }
        };
        $list->expects($this->once())->method('getIterator')->willReturn($generator());

        $scheduler = new TaskScheduler($list, 2, $report, $output, $fastFinish);
        $scheduler->run();

        $result = [];
        foreach ($buffer->getTaskReports() as $report) {
            $result[$report->getTaskName()] = $report->getStatus();
        }

        $this->assertSame($expected, $result);
    }

    private static function start(string $toolName): string
    {
        return $toolName . ' starting';
    }

    private static function end(string $toolName): string
    {
        return $toolName . ' finished';
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function succeedingTask(string $toolName): ReportWritingTaskInterface
    {
        return $this->mockTask($toolName, ReportInterface::STATUS_PASSED);
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function failingTask(string $toolName): ReportWritingTaskInterface
    {
        return $this->mockTask($toolName, ReportInterface::STATUS_FAILED);
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function throwingTask(string $toolName): ReportWritingTaskInterface
    {
        return $this->mockTask($toolName, new RuntimeException('fail miserably'));
    }

    /** @param string|Throwable|null $result */
    private function mockTask(?string $toolName, $result): ReportWritingTaskInterface
    {
        $mock = $this->createMock(ReportWritingTaskInterface::class);
        $mock->method('getToolName')->willReturn($toolName);
        // Should be skipped.
        if (null === $result) {
            $mock->expects($this->never())->method('runWithReport');
            return $mock;
        }

        $mock
            ->method('runWithReport')
            ->willReturnCallback(function (TaskReportInterface $report) use ($result) {
                if ($result instanceof Throwable) {
                    $report->close(ReportInterface::STATUS_FAILED);
                    throw $result;
                }
                $report->close($result);
            });

        return $mock;
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function succeedingParallelTask(
        int $tickDuration,
        string $toolName,
        int $cost = 1
    ): ReportWritingParallelTaskInterface {
        return $this->mockParallelizableTask($tickDuration, $cost, $toolName, ReportInterface::STATUS_PASSED);
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function failingParallelTask(
        int $tickDuration,
        string $toolName,
        int $cost = 1
    ): ReportWritingParallelTaskInterface {
        return $this->mockParallelizableTask($tickDuration, $cost, $toolName, ReportInterface::STATUS_FAILED);
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
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
        $mock = $this->createMock(ReportWritingParallelTaskInterface::class);
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
            ->willReturnCallback(function (TaskReportInterface $report) use ($result, &$taskReport) {
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
