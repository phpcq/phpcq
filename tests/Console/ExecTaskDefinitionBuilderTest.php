<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Console;

use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersionInterface;
use Phpcq\Runner\Config\ProjectConfiguration;
use Phpcq\Runner\Console\Definition\ExecTaskDefinitionBuilder;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Console\Definition\ExecTaskDefinitionBuilder */
final class ExecTaskDefinitionBuilderTest extends TestCase
{
    public function testDefaults(): void
    {
        $projectConfiguration = $this->createMock(ProjectConfiguration::class);

        $version = $this->createMock(PhpFilePluginVersionInterface::class);
        $version->expects($this->once())->method('getName')->willReturn('phar-2');
        $version->expects($this->exactly(2))
            ->method('getFilePath')
            ->willReturn($this->getBootstrap('phar-2~1.1.0.php'));
        $plugin = new InstalledPlugin($version, []);

        $installed = new InstalledRepository();
        $installed->addPlugin($plugin);

        $pluginRegistry = PluginRegistry::buildFromInstalledRepository($installed);

        $builder = new ExecTaskDefinitionBuilder(
            $projectConfiguration,
            $pluginRegistry,
            $installed,
            ['/usr/bin/php', []],
            sys_get_temp_dir()
        );

        $definition = $builder->build();

        self::assertCount(2, $definition->getApplications());
        self::assertSame('phar-2:foo', $definition->getApplication('phar-2:foo')->getName());
        self::assertSame('phar-2:bar', $definition->getApplication('phar-2:bar')->getName());
    }

    private function getBootstrap(string $fileName): string
    {
        return __DIR__ . '/../fixtures/repositories/installed-repository/' . $fileName;
    }
}
