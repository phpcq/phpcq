<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersionInterface;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Plugin\PluginRegistry
 */
class PluginRegistryTest extends TestCase
{
    public function testLoadFromInstalledRepository(): void
    {
        $version1 = $this->getMockForAbstractClass(PhpFilePluginVersionInterface::class);
        $version1->expects($this->once())->method('getName')->willReturn('tool1');

        $version2 = $this->getMockForAbstractClass(PhpFilePluginVersionInterface::class);
        $version2->expects($this->once())->method('getName')->willReturn('tool2');

        $instance = new InstalledRepository();
        $instance->addPlugin(new InstalledPlugin($version1));
        $instance->addPlugin(new InstalledPlugin($version2));

        $version1->expects($this->once())->method('getFilePath')->willReturn($this->getBootstrap('phar-1~1.0.0.php'));
        $version2->expects($this->once())->method('getFilePath')->willReturn($this->getBootstrap('phar-2~1.1.0.php'));

        $registry = PluginRegistry::buildFromInstalledRepository($instance);
        $this->assertInstanceOf(PluginRegistry::class, $registry);
    }

    private function getBootstrap(string $fileName): string
    {
        return __DIR__ . '/../fixtures/repositories/installed-repository/' . $fileName;
    }
}
