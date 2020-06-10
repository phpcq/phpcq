<?php

declare(strict_types=1);

namespace Phpcq\Test\Plugin;

use Phpcq\Plugin\PluginRegistry;
use Phpcq\PluginApi\Version10\PluginInterface;
use Phpcq\Repository\InstalledBootstrap;
use Phpcq\Repository\Repository;
use Phpcq\Repository\ToolInformationInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Plugin\PluginRegistry
 */
class PluginRegistryTest extends TestCase
{
    public function testLoadFromInstalledRepository(): void
    {
        $repository = new Repository();
        $repository->addVersion($tool1 = $this->getMockForAbstractClass(ToolInformationInterface::class));
        $repository->addVersion($tool2 = $this->getMockForAbstractClass(ToolInformationInterface::class));

        $tool1->expects($this->once())->method('getBootstrap')->willReturn($this->getBootstrap('phar-1~1.0.0.php'));
        $tool2->expects($this->once())->method('getBootstrap')->willReturn($this->getBootstrap('phar-2~1.1.0.php'));

        $this->assertSame([$tool1, $tool2], iterator_to_array($repository));

        $registry = PluginRegistry::buildFromInstalledRepository($repository);
        $this->assertInstanceOf(PluginInterface::class, $registry->getPluginByName('phar-1'));
        $this->assertInstanceOf(PluginInterface::class, $registry->getPluginByName('phar-2'));
    }

    private function getBootstrap(string $fileName)
    {
        return new InstalledBootstrap(
            '1.0.0',
            __DIR__ . '/../fixtures/repositories/installed-repository/' . $fileName,
            null
        );
    }
}
