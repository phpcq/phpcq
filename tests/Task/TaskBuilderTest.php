<?php

declare(strict_types=1);

namespace Phpcq\Test\Task;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Task\ParallelizableProcessTask;
use Phpcq\Task\ProcessTask;
use Phpcq\Task\TaskBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @covers \Phpcq\Task\TaskBuilder
 */
final class TaskBuilderTest extends TestCase
{
    public function testBuilds(): void
    {
        $tool = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('task-name');

        $builder = new TaskBuilder(
            $tool,
            ['foo', 'bar', 'baz']
        );

        $builder
            ->forceSingleProcess()
            ->withWorkingDirectory('/path/to/working-directory')
            ->withEnv(['var1' => 'value1', 'var2' => 'value2"'])
            ->withInput('input-values')
            ->withTimeout(3600);

        $runner = $builder->build();

        $this->assertInstanceOf(ProcessTask::class, $runner);

        // This is ugly as hell but no idea how to check otherwise...
        $this->assertPrivateProperty(['foo', 'bar', 'baz'], 'command', $runner);
        $this->assertPrivateProperty('/path/to/working-directory', 'cwd', $runner);
        $this->assertPrivateProperty(['var1' => 'value1', 'var2' => 'value2"'], 'env', $runner);
        $this->assertPrivateProperty('input-values', 'input', $runner);
        $this->assertPrivateProperty(3600.0, 'timeout', $runner);
    }

    public function testThrowsExceptionWhenTryingToSetCostOnSingleThread(): void
    {
        $tool = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('task-name');

        $builder = new TaskBuilder($tool, ['foo']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Can not set cost for single process forced task.');

        $builder
            ->forceSingleProcess()
            ->withCosts(10);
    }

    public function testThrowsExceptionWhenTryingToSetSingleThreadOnTaskWithHigherCostThanOne(): void
    {
        $tool = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('task-name');

        $builder = new TaskBuilder($tool, ['foo']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Can not force task with cost > 1 to run as single process');

        $builder
            ->withCosts(10)
            ->forceSingleProcess();
    }

    public function testBuildsParallel(): void
    {
        $tool = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('task-name');

        $builder = new TaskBuilder(
            $tool,
            ['foo', 'bar', 'baz']
        );

        $builder
            ->withWorkingDirectory('/path/to/working-directory')
            ->withEnv(['var1' => 'value1', 'var2' => 'value2"'])
            ->withInput('input-values')
            ->withTimeout(3600);

        $runner = $builder->build();

        $this->assertInstanceOf(ParallelizableProcessTask::class, $runner);

        // This is ugly as hell but no idea how to check otherwise...
        $this->assertPrivateProperty(['foo', 'bar', 'baz'], 'command', $runner);
        $this->assertPrivateProperty('/path/to/working-directory', 'cwd', $runner);
        $this->assertPrivateProperty(['var1' => 'value1', 'var2' => 'value2"'], 'env', $runner);
        $this->assertPrivateProperty('input-values', 'input', $runner);
        $this->assertPrivateProperty(3600.0, 'timeout', $runner);
    }

    private static function assertPrivateProperty($expected, string $property, object $instance): void
    {
        $reflection = new ReflectionProperty($instance, $property);
        $reflection->setAccessible(true);
        self::assertSame($expected, $reflection->getValue($instance));
    }
}
