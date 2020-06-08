<?php

declare(strict_types=1);

namespace Phpcq\Test\Task;

use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Task\ConsoleWritingExecTaskRunner;
use Phpcq\Task\ReportWritingProcessTaskRunner;
use Phpcq\Task\TaskRunnerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @covers \Phpcq\Task\TaskRunnerBuilder
 */
final class TaskRunnerBuilderTest extends TestCase
{
    public function testBuilds(): void
    {
        $builder = new TaskRunnerBuilder(
            'tool-name',
            ['foo', 'bar', 'baz'],
            $this->getMockForAbstractClass(ToolReportInterface::class)
        );

        $builder
            ->withWorkingDirectory('/path/to/working-directory')
            ->withEnv(['var1' => 'value1', 'var2' => 'value2"'])
            ->withInput('input-values')
            ->withTimeout(3600);

        $runner = $builder->build();

        $this->assertInstanceOf(ReportWritingProcessTaskRunner::class, $runner);

        // This is ugly as hell but no idea how to check otherwise...
        $this->assertPrivateProperty(['foo', 'bar', 'baz'], 'command', $runner);
        $this->assertPrivateProperty('/path/to/working-directory', 'cwd', $runner);
        $this->assertPrivateProperty(['var1' => 'value1', 'var2' => 'value2"'], 'env', $runner);
        $this->assertPrivateProperty('input-values', 'input', $runner);
        $this->assertPrivateProperty(3600.0, 'timeout', $runner);
    }

    public function testBuildExecRunner(): void
    {
        $builder = new TaskRunnerBuilder(
            'tool-name',
            ['foo', 'bar', 'baz'],
            null
        );

        $builder
            ->withWorkingDirectory('/path/to/working-directory')
            ->withEnv(['var1' => 'value1', 'var2' => 'value2"'])
            ->withInput('input-values')
            ->withTimeout(3600);

        $runner = $builder->build();

        $this->assertInstanceOf(ConsoleWritingExecTaskRunner::class, $runner);

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
