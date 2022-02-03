<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Updater\Task\Plugin\KeepPluginTask;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Plugin\KeepPluginTask */
final class KeepPluginTaskTest extends TestCase
{
    public function testDescription(): void
    {
        $pluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $pluginVersion->expects($this->once())->method('getName')->willReturn('foo');
        $pluginVersion->expects($this->once())->method('getVersion')->willReturn('1.0.1');

        $installedVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);

        $instance = new KeepPluginTask($pluginVersion, $installedVersion);

        self::assertSame(
            'Will keep plugin foo in version 1.0.1',
            $instance->getPurposeDescription()
        );
    }
}
