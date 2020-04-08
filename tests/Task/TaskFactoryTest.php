<?php

declare(strict_types=1);

namespace Phpcq\Test\Task;

use Phpcq\Repository\RepositoryInterface;
use Phpcq\Repository\ToolInformationInterface;
use Phpcq\Task\TaskFactory;
use Phpcq\Task\TaskRunnerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @covers \Phpcq\Task\TaskFactory
 */
final class TaskFactoryTest extends TestCase
{
    public function testBuildRunProcess(): void
    {
        $factory = new TaskFactory(
            '/phpcq/path',
            $this->getMockForAbstractClass(RepositoryInterface::class),
            '/path/to/php-cli',
            ['php', 'arguments']
        );

        $builder = $factory->buildRunProcess(['command', 'arg1', 'arg2']);

        $this->assertInstanceOf(TaskRunnerBuilder::class, $builder);
        $this->assertPrivateProperty(['command', 'arg1', 'arg2'], 'command', $builder);
    }

    public function testBuildRunPhar(): void
    {
        $factory = new TaskFactory(
            '/phpcq/path',
            $installed = $this->getMockForAbstractClass(RepositoryInterface::class),
            '/path/to/php-cli',
            ['php', 'arguments']
        );

        $installed
            ->expects(self::atLeastOnce())
            ->method('getTool')
            ->with('phar-name', '*')->willReturn($tool = $this->getMockForAbstractClass(ToolInformationInterface::class));

        $tool->expects(self::atLeastOnce())->method('getPharUrl')->willReturn('phar-file-name.phar');

        $builder = $factory->buildRunPhar('phar-name', ['phar-arg1', 'phar-arg2']);

        $this->assertInstanceOf(TaskRunnerBuilder::class, $builder);
        $this->assertPrivateProperty([
            '/path/to/php-cli',
            'php', 'arguments',
            '/phpcq/path/phar-file-name.phar',
            'phar-arg1', 'phar-arg2',
        ], 'command', $builder);
    }

    private static function assertPrivateProperty($expected, string $property, object $instance): void
    {
        $reflection = new ReflectionProperty($instance, $property);
        $reflection->setAccessible(true);
        self::assertSame($expected, $reflection->getValue($instance));
    }
}