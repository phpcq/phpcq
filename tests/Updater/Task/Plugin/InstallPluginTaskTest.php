<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Updater\Task\Plugin\InstallPluginTask;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Plugin\InstallPluginTaskTest */
final class InstallPluginTaskTest extends TestCase
{
    public function testDescription(): void
    {
        $pluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $pluginVersion->expects($this->once())->method('getName')->willReturn('foo');
        $pluginVersion->expects($this->once())->method('getVersion')->willReturn('1.0.1');

        $instance = new InstallPluginTask($pluginVersion, true);

        self::assertSame(
            'Will install plugin foo in version 1.0.1',
            $instance->getPurposeDescription()
        );
    }
}
