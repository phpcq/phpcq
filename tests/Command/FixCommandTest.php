<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Command;

use Phpcq\PluginApi\Version10\DiagnosticsPluginInterface;
use Phpcq\PluginApi\Version10\FixPluginInterface;
use Phpcq\PluginApi\Version10\FixStage;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\PluginInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\PluginApi\Version10\Task\ReportWritingTaskInterface;
use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Phpcq\Runner\Command\FixCommand;
use Phpcq\Runner\Command\RunCommand;
use Phpcq\Runner\Config\PluginConfiguration;
use Phpcq\Runner\Environment;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Phpcq\Runner\Report\Report;
use Phpcq\Runner\Task\FixTasklist;
use Phpcq\Runner\Task\ResolvedTask;
use Phpcq\Runner\Task\Tasklist;
use Phpcq\Runner\Test\TemporaryFileProducingTestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use ReflectionMethod;
use ReflectionProperty;

use function iterator_to_array;

/**
 * @covers \Phpcq\Runner\Command\FixCommand
 * @covers \Phpcq\Runner\Command\RunCommand
 * @covers \Phpcq\Runner\Command\AbstractTaskCommand
 */
final class FixCommandTest extends TestCase
{
    use TemporaryFileProducingTestTrait;

    public function testFixCommandSharesRunOptions(): void
    {
        $fix = new FixCommand();
        $run = new RunCommand();

        self::assertSame('fix', $fix->getName());
        self::assertSame('run', $run->getName());

        foreach (['fast-finish', 'exit-0', 'report', 'output', 'threshold', 'threads'] as $option) {
            self::assertTrue($fix->getDefinition()->hasOption($option), $option);
            self::assertTrue($run->getDefinition()->hasOption($option), $option);
        }
        self::assertTrue($fix->getDefinition()->hasArgument('task'));
        self::assertSame('default', $fix->getDefinition()->getArgument('task')->getDefault());
    }

    public function testCollectsFixTasksInStageOrder(): void
    {
        $format   = self::createStub(TaskInterface::class);
        $refactor = self::createStub(TaskInterface::class);
        $command  = $this->createInitializedCommand();

        $this->collectTasks($command, ['config' => []], $this->createFixPlugin(FixStage::Format, [$format]));
        $this->collectTasks(
            $command,
            ['config' => [], 'fix-stage' => 'refactor'],
            $this->createFixPlugin(FixStage::Format, [$refactor])
        );

        self::assertSame([$refactor, $format], iterator_to_array($this->getFixTasks($command), false));
    }

    public function testAddsDiagnosticTasksOfPluginsWithoutFixInterface(): void
    {
        $diagnostic = self::createStub(TaskInterface::class);
        $plugin     = $this->createMock(DiagnosticsPluginInterface::class);
        $plugin->expects(self::once())->method('createDiagnosticTasks')->willReturnCallback(
            static function () use ($diagnostic): \Generator {
                yield $diagnostic;
            }
        );

        $command  = $this->createInitializedCommand();
        $taskList = $this->collectTasks($command, ['config' => [], 'fix-stage' => 'format'], $plugin);

        self::assertSame([$diagnostic], iterator_to_array($taskList, false));
        self::assertSame([], iterator_to_array($this->getFixTasks($command), false));
    }

    public function testStopsFixTasksAfterFailureButRunsDiagnostics(): void
    {
        $order   = [];
        $command = $this->createInitializedCommand();
        $this->setInput($command, []);
        $fixTasks = $this->getFixTasks($command);
        $fixTasks->addFixTask($this->createTask('fix-a', TaskReportInterface::STATUS_FAILED, $order), FixStage::Format);
        $fixTasks->addFixTask($this->createTask('fix-b', TaskReportInterface::STATUS_PASSED, $order), FixStage::Format);
        $taskList = new Tasklist();
        $taskList->add($this->createTask('diagnostic', TaskReportInterface::STATUS_PASSED, $order));

        self::assertFalse($this->executeTasks($command, $taskList));
        self::assertSame(['fix-a', 'diagnostic'], $order);
    }

    public function testSkipsDiagnosticsAfterFailedFixTaskWithFastFinish(): void
    {
        $order   = [];
        $command = $this->createInitializedCommand();
        $this->setInput($command, ['--fast-finish' => true]);
        $this->getFixTasks($command)
            ->addFixTask($this->createTask('fix-a', TaskReportInterface::STATUS_FAILED, $order), FixStage::Format);
        $taskList = new Tasklist();
        $taskList->add($this->createTask('diagnostic', TaskReportInterface::STATUS_PASSED, $order));

        self::assertFalse($this->executeTasks($command, $taskList));
        self::assertSame(['fix-a'], $order);
    }

    private function createInitializedCommand(): FixCommand
    {
        $command = new FixCommand();
        (new ReflectionProperty($command, 'fixTasks'))->setValue($command, new FixTasklist());

        return $command;
    }

    private function collectTasks(FixCommand $command, array $taskConfig, PluginInterface $plugin): Tasklist
    {
        $taskList = new Tasklist();
        (new ReflectionMethod($command, 'handleTask'))->invoke(
            $command,
            new ResolvedTask(
                'task',
                $taskConfig,
                $plugin,
                self::createStub(PluginConfiguration::class),
                self::createStub(Environment::class)
            ),
            $taskList
        );

        return $taskList;
    }

    private function setInput(FixCommand $command, array $parameters): void
    {
        $input = new ArrayInput($parameters + ['--threads' => '1'], $command->getDefinition());
        (new ReflectionProperty($command, 'input'))->setValue($command, $input);
    }

    private function executeTasks(FixCommand $command, Tasklist $taskList): bool
    {
        $result = (new ReflectionMethod($command, 'executeTasks'))->invoke(
            $command,
            $taskList,
            new Report(new ReportBuffer(), self::$tempdir),
            self::createStub(OutputInterface::class)
        );
        self::assertIsBool($result);

        return $result;
    }

    /** @param list<string> $order */
    private function createTask(string $name, string $status, array &$order): ReportWritingTaskInterface
    {
        $task = self::createStub(ReportWritingTaskInterface::class);
        $task->method('getToolName')->willReturn($name);
        $task->method('runWithReport')->willReturnCallback(
            function (TaskReportInterface $report) use ($name, $status, &$order): void {
                $order[] = $name;
                $report->close($status);
            }
        );

        return $task;
    }

    private function getFixTasks(FixCommand $command): FixTasklist
    {
        $tasks = (new ReflectionProperty($command, 'fixTasks'))->getValue($command);
        self::assertInstanceOf(FixTasklist::class, $tasks);

        return $tasks;
    }

    /** @param list<TaskInterface> $tasks */
    private function createFixPlugin(FixStage $stage, array $tasks): FixPluginInterface
    {
        $plugin = $this->createMock(FixPluginInterface::class);
        $plugin->method('getFixStage')->willReturn($stage);
        $plugin->expects(self::once())->method('createFixTasks')->willReturnCallback(
            static function () use ($tasks): \Generator {
                yield from $tasks;
            }
        );

        return $plugin;
    }
}
