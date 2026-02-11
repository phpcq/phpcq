<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Task;

use Phpcq\PluginApi\Version10\Task\PhpTaskBuilderInterface;
use Phpcq\PluginApi\Version10\Task\TaskBuilderInterface;
use Phpcq\PluginApi\Version10\Task\TaskFactoryInterface;
use Phpcq\Runner\Task\SingleProcessTaskFactory;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Task\SingleProcessTaskFactory */
final class SingleProcessTaskFactoryTest extends TestCase
{
    public function testBuildRunProcess(): void
    {
        $builder = $this->mockSingleForcingTaskBuilder();
        $factory = $this->createMock(TaskFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('buildRunProcess')
            ->with('foo', ['bar'])
            ->willReturn($builder);

        $instance = new SingleProcessTaskFactory($factory);
        $createdBuilder = $instance->buildRunProcess('foo', ['bar']);

        self::assertSame($builder, $createdBuilder);
    }

    public function testBuildRunPhar(): void
    {
        $builder = $this->mockSingleForcingTaskBuilder(PhpTaskBuilderInterface::class);
        $factory = $this->createMock(TaskFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('buildRunPhar')
            ->with('foo', ['bar'])
            ->willReturn($builder);

        $instance = new SingleProcessTaskFactory($factory);
        $createdBuilder = $instance->buildRunPhar('foo', ['bar']);

        self::assertSame($builder, $createdBuilder);
    }

    public function testBuildPhpProcess(): void
    {
        $builder = $this->mockSingleForcingTaskBuilder(PhpTaskBuilderInterface::class);
        $factory = $this->createMock(TaskFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('buildPhpProcess')
            ->with('foo', ['bar'])
            ->willReturn($builder);

        $instance = new SingleProcessTaskFactory($factory);
        $createdBuilder = $instance->buildPhpProcess('foo', ['bar']);

        self::assertSame($builder, $createdBuilder);
    }

    /** @return TaskBuilderInterface&MockObject */
    protected function mockSingleForcingTaskBuilder(
        string $instance = TaskBuilderInterface::class
    ): TaskBuilderInterface {
        $builder = $this->createMock($instance);
        $builder
            ->expects($this->once())
            ->method('forceSingleProcess')
            ->willReturn($builder);

        return $builder;
    }
}
