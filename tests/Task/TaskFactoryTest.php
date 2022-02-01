<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Task;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Task\TaskBuilderPhp;
use Phpcq\Runner\Task\TaskFactory;
use Phpcq\Runner\Task\TaskBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @covers \Phpcq\Runner\Task\TaskFactory
 */
final class TaskFactoryTest extends TestCase
{
    public function testBuildRunProcess(): void
    {
        $tool = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('task-name');

        $factory = new TaskFactory(
            'test',
            new InstalledPlugin($this->getMockForAbstractClass(PluginVersionInterface::class), [$tool]),
            '/path/to/php-cli',
            ['php', 'arguments']
        );

        $builder = $factory->buildRunProcess('task-name', ['command', 'arg1', 'arg2']);

        $this->assertInstanceOf(TaskBuilder::class, $builder);
        $this->assertPrivateProperty(['command', 'arg1', 'arg2'], 'command', $builder);
    }

    public function testBuildRunPhar(): void
    {
        $tool = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('phar-name');

        $factory = new TaskFactory(
            'test',
            new InstalledPlugin($this->getMockForAbstractClass(PluginVersionInterface::class), [$tool]),
            '/path/to/php-cli',
            ['php', 'arguments']
        );

        $tool->expects(self::atLeastOnce())->method('getPharUrl')->willReturn('/phar-file-name.phar');

        $builder = $factory->buildRunPhar('phar-name', ['phar-arg1', 'phar-arg2']);

        $this->assertInstanceOf(TaskBuilderPhp::class, $builder);
        $this->assertPrivateProperty('/path/to/php-cli', 'phpCliBinary', $builder);
        $this->assertPrivateProperty(['php', 'arguments'], 'phpArguments', $builder);
        $this->assertPrivateProperty(['/phar-file-name.phar', 'phar-arg1', 'phar-arg2'], 'arguments', $builder);
    }

    public function testBuildPhpProcess(): void
    {
        $tool = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('task-name');

        $factory = new TaskFactory(
            'task-name',
            new InstalledPlugin($this->getMockForAbstractClass(PluginVersionInterface::class), [$tool]),
            '/path/to/php-cli',
            ['php', 'arguments']
        );

        $builder = $factory->buildPhpProcess('task-name', ['command', 'arg1', 'arg2']);

        $this->assertInstanceOf(TaskBuilderPhp::class, $builder);
        $this->assertPrivateProperty('/path/to/php-cli', 'phpCliBinary', $builder);
        $this->assertPrivateProperty(['php', 'arguments'], 'phpArguments', $builder);
        $this->assertPrivateProperty(['command', 'arg1', 'arg2'], 'arguments', $builder);
    }

    private function assertPrivateProperty($expected, string $property, object $instance): void
    {
        $reflection = new ReflectionProperty($instance, $property);
        $reflection->setAccessible(true);
        self::assertSame($expected, $reflection->getValue($instance));
    }
}
