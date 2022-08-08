<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config;

use Phpcq\PluginApi\Version10\Configuration\OptionsInterface;
use Phpcq\Runner\Config\PhpcqConfiguration;
use Phpcq\Runner\Config\PluginConfigurationFactory;
use Phpcq\Runner\Environment;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\Runner\Repository\InstalledRepositoryLoader;
use PHPUnit\Framework\TestCase;

use function realpath;

/** @covers \Phpcq\Runner\Config\PluginConfigurationFactory */
final class PluginConfigurationFactoryTest extends TestCase
{
    public function testDefaultDirectories(): void
    {
        $phcqOptions = $this->getMockForAbstractClass(OptionsInterface::class);
        $phcqOptions
            ->expects($this->once())
            ->method('getOptions')
            ->with('tasks')
            ->willReturn(
                [
                    'example' => [
                        'plugin' => 'phar-1',
                    ],
                ]
            );
        $phcqOptions
            ->expects($this->once())
            ->method('getStringList')
            ->willReturnArgument('directories')
            ->willReturn(['src', 'test']);

        $installedRepository = (new InstalledRepositoryLoader())->loadFile(
            realpath(__DIR__ . '/../fixtures/repositories/installed-repository/installed.json')
        );

        $pluginRegistry     = PluginRegistry::buildFromInstalledRepository($installedRepository);
        $phpcqConfiguration = new PhpcqConfiguration($phcqOptions);
        $environment        = $this->createMock(Environment::class);
        $instance           = new PluginConfigurationFactory(
            $phpcqConfiguration,
            $pluginRegistry,
            $installedRepository
        );

        $pluginConfiguration = $instance->createForTask('example', $environment);

        self::assertSame(['src', 'test'], $pluginConfiguration->getStringList('directories'));
    }

    public function testCustomDirectories(): void
    {
        $phcqOptions = $this->getMockForAbstractClass(OptionsInterface::class);
        $phcqOptions
            ->expects($this->once())
            ->method('getOptions')
            ->with('tasks')
            ->willReturn(
                [
                    'example' => [
                        'plugin'      => 'phar-1',
                        'directories' => [
                            'foo',
                            'bar',
                        ],
                    ],
                ]
            );
        $phcqOptions
            ->expects($this->never())
            ->method('getStringList')
            ->willReturnArgument('directories')
            ->willReturn(['src', 'test']);

        $installedRepository = (new InstalledRepositoryLoader())->loadFile(
            realpath(__DIR__ . '/../fixtures/repositories/installed-repository/installed.json')
        );

        $pluginRegistry     = PluginRegistry::buildFromInstalledRepository($installedRepository);
        $phpcqConfiguration = new PhpcqConfiguration($phcqOptions);
        $environment        = $this->createMock(Environment::class);
        $instance           = new PluginConfigurationFactory(
            $phpcqConfiguration,
            $pluginRegistry,
            $installedRepository
        );

        $pluginConfiguration = $instance->createForTask('example', $environment);

        self::assertSame(['foo', 'bar'], $pluginConfiguration->getStringList('directories'));
    }
}
