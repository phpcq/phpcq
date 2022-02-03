<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Tool;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Updater\Task\Tool\InstallToolTask;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Tool\InstallToolTask */
final class InstallTookTaskTest extends TestCase
{
    public function testDescription(): void
    {
        $pluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $toolVersion = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $toolVersion->expects($this->once())->method('getName')->willReturn('foo');
        $toolVersion->expects($this->once())->method('getVersion')->willReturn('1.0.1');

        $instance = new InstallToolTask($pluginVersion, $toolVersion, true);

        self::assertSame(
            'Will install tool foo in version 1.0.1',
            $instance->getPurposeDescription()
        );
    }
}
