<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Task;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\Runner\Task\ParallelizableProcessTask;
use Phpcq\Runner\Task\ProcessTask;
use Phpcq\Runner\Task\TaskBuilderPhp;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @covers \Phpcq\Runner\Task\AbstractTaskBuilder
 * @covers \Phpcq\Runner\Task\TaskBuilderPhp
 */
final class TaskBuilderPhpTest extends TestCase
{
    public function testBuilds(): void
    {
        $builder = $this->createBuilder();

        $builder
            ->forceSingleProcess()
            ->withWorkingDirectory('/path/to/working-directory')
            ->withEnv(['var1' => 'value1', 'var2' => 'value2"'])
            ->withInput('input-values')
            ->withTimeout(3600);

        $runner = $builder->build();

        $this->assertInstanceOf(ProcessTask::class, $runner);

        // This is ugly as hell but no idea how to check otherwise...
        $this->assertPrivateProperty(['/php-cli', 'arg1', 'arg2', 'arg3', 'foo', 'bar', 'baz'], 'command', $runner);
        $this->assertPrivateProperty('/path/to/working-directory', 'cwd', $runner);
        $this->assertPrivateProperty(['var1' => 'value1', 'var2' => 'value2"'], 'env', $runner);
        $this->assertPrivateProperty('input-values', 'input', $runner);
        $this->assertPrivateProperty(3600.0, 'timeout', $runner);
    }

    public function testThrowsExceptionWhenTryingToSetCostOnSingleThread(): void
    {
        $builder = $this->createBuilder();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Can not set cost for single process forced task.');

        $builder
            ->forceSingleProcess()
            ->withCosts(10);
    }

    public function testThrowsExceptionWhenTryingToSetSingleThreadOnTaskWithHigherCostThanOne(): void
    {
        $builder = $this->createBuilder();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Can not force task with cost > 1 to run as single process');

        $builder
            ->withCosts(10)
            ->forceSingleProcess();
    }

    public function testBuildsParallel(): void
    {
        $builder = $this->createBuilder();

        $builder
            ->withWorkingDirectory('/path/to/working-directory')
            ->withEnv(['var1' => 'value1', 'var2' => 'value2"'])
            ->withInput('input-values')
            ->withTimeout(3600);

        $runner = $builder->build();

        $this->assertInstanceOf(ParallelizableProcessTask::class, $runner);

        // This is ugly as hell but no idea how to check otherwise...
        $this->assertPrivateProperty(['/php-cli', 'arg1', 'arg2', 'arg3', 'foo', 'bar', 'baz'], 'command', $runner);
        $this->assertPrivateProperty('/path/to/working-directory', 'cwd', $runner);
        $this->assertPrivateProperty(['var1' => 'value1', 'var2' => 'value2"'], 'env', $runner);
        $this->assertPrivateProperty('input-values', 'input', $runner);
        $this->assertPrivateProperty(3600.0, 'timeout', $runner);
    }

    public function testDisablesXDebug(): void
    {
        $builder = $this->createBuilder();

        $builder
            ->withoutXDebug()
            ->withWorkingDirectory('/path/to/working-directory')
            ->withEnv(['var1' => 'value1', 'var2' => 'value2"'])
            ->withInput('input-values')
            ->withTimeout(3600);

        $runner = $builder->build();

        $this->assertInstanceOf(ParallelizableProcessTask::class, $runner);

        // This is ugly as hell but no idea how to check otherwise...
        $this->assertPrivateProperty(
            ['/php-cli', 'arg1', 'arg2', 'arg3', '-dxdebug.mode=off', 'foo', 'bar', 'baz'],
            'command',
            $runner
        );
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

    private function createBuilder(): TaskBuilderPhp
    {
        return new TaskBuilderPhp(
            'task-name',
            '/php-cli',
            ['arg1', 'arg2', 'arg3'],
            ['foo', 'bar', 'baz'],
            []
        );
    }
}
