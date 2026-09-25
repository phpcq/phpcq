<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Task;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Task\AbstractTaskBuilder;
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
        $tool = $this->createMock(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('task-name');

        $factory = new TaskFactory(
            'test',
            new InstalledPlugin($this->createMock(PluginVersionInterface::class), [$tool]),
            '/path/to/php-cli',
            ['php', 'arguments']
        );

        $builder = $factory->buildRunProcess('task-name', ['command', 'arg1', 'arg2']);

        $this->assertInstanceOf(TaskBuilder::class, $builder);
        $this->assertPrivateProperty(['command', 'arg1', 'arg2'], 'command', $builder);
    }

    public function testBuildRunPhar(): void
    {
        $tool = $this->createMock(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('phar-name');

        $factory = new TaskFactory(
            'test',
            new InstalledPlugin($this->createMock(PluginVersionInterface::class), [$tool]),
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
        $tool = $this->createMock(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('task-name');

        $factory = new TaskFactory(
            'task-name',
            new InstalledPlugin($this->createMock(PluginVersionInterface::class), [$tool]),
            '/path/to/php-cli',
            ['php', 'arguments']
        );

        $builder = $factory->buildPhpProcess('task-name', ['command', 'arg1', 'arg2']);

        $this->assertInstanceOf(TaskBuilderPhp::class, $builder);
        $this->assertPrivateProperty('/path/to/php-cli', 'phpCliBinary', $builder);
        $this->assertPrivateProperty(['php', 'arguments'], 'phpArguments', $builder);
        $this->assertPrivateProperty(['command', 'arg1', 'arg2'], 'arguments', $builder);
    }

    public function testMetadataContainsVersionOfPharTool(): void
    {
        $tool = $this->createMock(ToolVersionInterface::class);
        $tool->method('getName')->willReturn('tool');
        $tool->method('getVersion')->willReturn('1.2.3');

        $metadata = $this->buildMetadata(
            new InstalledPlugin($this->mockPluginVersion(), [$tool], null, ['vendor/tool' => '2.0.0']),
            'tool'
        );

        self::assertSame('tool', $metadata['tool_name']);
        self::assertSame('1.2.3', $metadata['tool_version']);
    }

    public function testMetadataContainsVersionOfComposerPackageMatchingToolName(): void
    {
        $metadata = $this->buildMetadata(
            new InstalledPlugin(
                $this->mockPluginVersion(),
                [],
                null,
                ['vendor/other' => '1.0.0', 'vendor/tool' => '2.0.0']
            ),
            'tool'
        );

        self::assertSame('tool', $metadata['tool_name']);
        self::assertSame('2.0.0', $metadata['tool_version']);
    }

    public function testMetadataContainsNoVersionIfComposerPackageIsAmbiguous(): void
    {
        $metadata = $this->buildMetadata(
            new InstalledPlugin(
                $this->mockPluginVersion(),
                [],
                null,
                ['vendor/tool' => '1.0.0', 'other-vendor/tool' => '2.0.0']
            ),
            'tool'
        );

        self::assertArrayNotHasKey('tool_name', $metadata);
        self::assertArrayNotHasKey('tool_version', $metadata);
    }

    public function testMetadataContainsNoVersionIfNoComposerPackageMatches(): void
    {
        $metadata = $this->buildMetadata(
            new InstalledPlugin($this->mockPluginVersion(), [], null, ['vendor/tool-extension' => '1.0.0']),
            'tool'
        );

        self::assertArrayNotHasKey('tool_name', $metadata);
        self::assertArrayNotHasKey('tool_version', $metadata);
    }

    private function mockPluginVersion(): PluginVersionInterface
    {
        $pluginVersion = $this->createMock(PluginVersionInterface::class);
        $pluginVersion->method('getName')->willReturn('plugin');
        $pluginVersion->method('getVersion')->willReturn('1.0.0');

        return $pluginVersion;
    }

    /** @return array<string,string> */
    private function buildMetadata(InstalledPlugin $installed, string $toolName): array
    {
        $factory = new TaskFactory('test', $installed, '/path/to/php-cli', []);
        $builder = $factory->buildRunProcess($toolName, ['command']);

        return (new ReflectionProperty(AbstractTaskBuilder::class, 'metadata'))->getValue($builder);
    }

    private function assertPrivateProperty($expected, string $property, object $instance): void
    {
        $reflection = new ReflectionProperty($instance, $property);
        self::assertSame($expected, $reflection->getValue($instance));
    }
}
