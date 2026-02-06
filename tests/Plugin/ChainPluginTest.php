<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Plugin;

use Phpcq\PluginApi\Version10\Configuration\Builder\StringListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\Runner\Plugin\ChainPlugin;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Plugin\ChainPlugin
 */
final class ChainPluginTest extends TestCase
{
    public function testName(): void
    {
        $instance = new ChainPlugin();
        $this->assertEquals('chain', $instance->getName());
    }

    public function testInstalledPlugin(): void
    {
        $installedPlugin = ChainPlugin::createInstalledPlugin();

        $this->assertEquals('chain', $installedPlugin->getName());
        $this->assertEquals([], iterator_to_array($installedPlugin->iterateTools()));

        $pluginVersion = $installedPlugin->getPluginVersion();
        $expectedHash  = PluginHash::createForFile(__DIR__ . '/../../src/Resources/plugins/chain-plugin.php');
        $this->assertEquals('chain', $pluginVersion->getName());
        $this->assertEquals('1.0.0', $pluginVersion->getVersion());
        $this->assertEquals('1.0.0', $pluginVersion->getApiVersion());
        $this->assertTrue($expectedHash->equals($pluginVersion->getHash()));
        $this->assertNull($pluginVersion->getSignaturePath());
        $this->assertFileEquals(
            __DIR__ . '/../../src/Resources/plugins/chain-plugin.php',
            $pluginVersion->getFilePath()
        );
    }

    public function testConfigurationDescription(): void
    {
        $instance             = new ChainPlugin();
        $configOptionsBuilder = $this->createMock(PluginConfigurationBuilderInterface::class);
        $tasksOption          = $this->createMock(StringListOptionBuilderInterface::class);
        $configOptionsBuilder->expects($this->once())
            ->method('describeStringListOption')
            ->with('tasks', 'The list of tasks which are executed when running the task')
            ->willReturn($tasksOption);

        $tasksOption
            ->expects($this->once())
            ->method('isRequired');

        $instance->describeConfiguration($configOptionsBuilder);
    }

    public function testTaskNames(): void
    {
        $instance = new ChainPlugin();
        $config = $this->createMock(PluginConfigurationInterface::class);
        $config
            ->expects($this->once())
            ->method('getStringList')
            ->willReturn(['foo', 'bar']);

        $this->assertEquals(['foo', 'bar'], $instance->getTaskNames($config));
    }
}
