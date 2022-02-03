<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Updater\Task\Plugin\KeepPluginTask;
use Phpcq\Runner\Updater\Task\Tool\KeepToolTask;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Plugin\KeepPluginTask */
final class KeepPluginTaskTest extends TestCase
{
    public function testDescription(): void
    {
        $pluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $pluginVersion->expects($this->once())->method('getName')->willReturn('foo');
        $pluginVersion->expects($this->once())->method('getVersion')->willReturn('1.0.1');

        $instance = new KeepPluginTask(new InstalledPlugin($pluginVersion));

        self::assertSame(
            'Will keep plugin foo in version 1.0.1',
            $instance->getPurposeDescription()
        );
    }
}
