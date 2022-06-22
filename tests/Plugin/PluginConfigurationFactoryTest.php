<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Plugin;

use Phpcq\PluginApi\Version10\ProjectConfigInterface;
use Phpcq\PluginApi\Version10\Task\TaskFactoryInterface;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersionInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Config\Options;
use Phpcq\Runner\Config\PhpcqConfiguration;
use Phpcq\Runner\Config\PluginConfigurationFactory;
use Phpcq\Runner\Environment;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;

/** @covers \Phpcq\Runner\Config\PluginConfigurationFactory */
final class PluginConfigurationFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $configuration = new PhpcqConfiguration(new Options([
            'plugins' => [
            ],
            'tasks' => [
                'test' => [
                    'plugin' => 'phar-3',
                    'config' => [
                        'rulesets' => ['standard.xml'],
                    ],
                ]
            ]
        ]));

        $installed = new InstalledRepository();
        $installed->addPlugin($this->mockPlugin('phar-3', 'phar-3~2.0.0.php'));

        $registry    = PluginRegistry::buildFromInstalledRepository($installed);
        $factory     = new PluginConfigurationFactory($configuration, $registry, $installed);
        $environment = new Environment(
            $this->getMockForAbstractClass(ProjectConfigInterface::class),
            $this->getMockForAbstractClass(TaskFactoryInterface::class),
            sys_get_temp_dir(),
            1,
            ''
        );

        $config = $factory->createForTask('test', $environment);

        self::assertSame(['standard.xml'], $config->getStringList('rulesets'));
    }

    public function testEnrichers(): void
    {
        $configuration = new PhpcqConfiguration(
            new Options(
                [
                    'plugins' => [
                    ],
                    'tasks' => [
                        'test' => [
                            'plugin' => 'phar-3',
                            'config' => [
                                'rulesets' => ['standard.xml'],
                            ],
                            'uses' => [
                                'enricher-a' => null,
                                'enricher-b' => [
                                    'strict' => true
                                ]
                            ]
                        ]
                    ]
                ]
            )
        );

        $installed = new InstalledRepository();
        $installed->addPlugin($this->mockPlugin('phar-3', 'phar-3~2.0.0.php'));
        $installed->addPlugin($this->mockPlugin('enricher-a', 'phar-enricher-a~1.0.0.php'));
        $installed->addPlugin($this->mockPlugin('enricher-b', 'phar-enricher-b~1.0.0.php'));

        $registry    = PluginRegistry::buildFromInstalledRepository($installed);
        $factory     = new PluginConfigurationFactory($configuration, $registry, $installed);
        $environment = new Environment(
            $this->getMockForAbstractClass(ProjectConfigInterface::class),
            $this->getMockForAbstractClass(TaskFactoryInterface::class),
            sys_get_temp_dir(),
            1,
            ''
        );

        $config = $factory->createForTask('test', $environment);

        self::assertSame(
            ['standard.xml', 'enricher-a.xml', 'enricher-b-strict.xml'],
            $config->getStringList('rulesets')
        );
    }

    private function getBootstrap(string $fileName): string
    {
        return __DIR__ . '/../fixtures/repositories/installed-repository/' . $fileName;
    }

    private function mockPlugin(string $name, string $bootstrap): InstalledPlugin
    {
        $version = $this->getMockForAbstractClass(PhpFilePluginVersionInterface::class);
        $version->expects($this->atLeastOnce())->method('getName')->willReturn($name);
        $version->expects($this->atLeastOnce())->method('getFilePath')->willReturn($this->getBootstrap($bootstrap));

        return new InstalledPlugin($version);
    }
}
