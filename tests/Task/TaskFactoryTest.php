<?php

declare(strict_types=1);

namespace Phpcq\Test\Task;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Task\TaskFactory;
use Phpcq\Task\TaskBuilder;
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
            new InstalledPlugin($this->getMockForAbstractClass(PluginVersionInterface::class), []),
            '/path/to/php-cli',
            ['php', 'arguments']
        );

        $builder = $factory->buildRunProcess('task-name', ['command', 'arg1', 'arg2']);

        $this->assertInstanceOf(TaskBuilder::class, $builder);
        $this->assertPrivateProperty(['command', 'arg1', 'arg2'], 'command', $builder);
    }

    public function testBuildRunPhar(): void
    {
        $factory = new TaskFactory(
            new InstalledPlugin(
                $this->getMockForAbstractClass(PluginVersionInterface::class),
                [
                    'phar-name' => $tool = $this->getMockForAbstractClass(ToolVersionInterface::class)
                ]
            ),
            '/path/to/php-cli',
            ['php', 'arguments']
        );

        $tool->expects(self::atLeastOnce())->method('getPharUrl')->willReturn('/phpcq/path/phar-file-name.phar');

        $builder = $factory->buildRunPhar('phar-name', ['phar-arg1', 'phar-arg2']);

        $this->assertInstanceOf(TaskBuilder::class, $builder);
        $this->assertPrivateProperty([
            '/path/to/php-cli',
            'php', 'arguments',
            '/phpcq/path/phar-file-name.phar',
            'phar-arg1', 'phar-arg2',
        ], 'command', $builder);
    }

    public function testBuildPhpProcess(): void
    {
        $factory = new TaskFactory(
            new InstalledPlugin($this->getMockForAbstractClass(PluginVersionInterface::class)),
            '/path/to/php-cli',
            ['php', 'arguments']
        );

        $builder = $factory->buildPhpProcess('task-name', ['command', 'arg1', 'arg2']);

        $this->assertInstanceOf(TaskBuilder::class, $builder);
        $this->assertPrivateProperty([
            '/path/to/php-cli',
            'php', 'arguments',
            'command', 'arg1', 'arg2'
        ], 'command', $builder);
    }

    private function assertPrivateProperty($expected, string $property, object $instance): void
    {
        $reflection = new ReflectionProperty($instance, $property);
        $reflection->setAccessible(true);
        self::assertSame($expected, $reflection->getValue($instance));
    }
}
