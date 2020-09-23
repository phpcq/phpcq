<?php

declare(strict_types=1);

namespace Phpcq\Test\Runner\Repository;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use PHPUnit\Framework\TestCase;
use function array_values;
use function iterator_to_array;

/** @covers \Phpcq\Runner\Repository\InstalledPlugin */
final class InstalledPluginTest extends TestCase
{
    public function testInstantiation(): void
    {
        $version = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $version->expects(self::once())->method('getName')->willReturn('plugin1');

        $tools   = ['tool1' => $this->getMockForAbstractClass(ToolVersionInterface::class)];
        $plugin  = new InstalledPlugin($version, $tools);

        self::assertSame('plugin1', $plugin->getName());
        self::assertSame($version, $plugin->getPluginVersion());
        self::assertSame(array_values($tools), iterator_to_array($plugin->iterateTools()));

        self::assertTrue($plugin->hasTool('tool1'));
        self::assertFalse($plugin->hasTool('tool2'));
    }

    public function testAddTool(): void
    {
        $version = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $plugin  = new InstalledPlugin($version);

        self::assertFalse($plugin->hasTool('foo'));

        $tool = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $tool->expects(self::once())->method('getName')->willReturn('foo');
        $plugin->addTool($tool);

        self::assertTrue($plugin->hasTool('foo'));
    }
}
