<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Tool;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Updater\Task\Tool\KeepToolTask;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Tool\KeepToolTask */
final class KeepToolTaskTest extends TestCase
{
    public function testDescription(): void
    {
        $pluginVersion = $this->createMock(PluginVersionInterface::class);
        $toolVersion = $this->createMock(ToolVersionInterface::class);
        $toolVersion->expects($this->once())->method('getName')->willReturn('foo');
        $toolVersion->expects($this->once())->method('getVersion')->willReturn('1.0.1');

        $installedVersion = $this->createMock(ToolVersionInterface::class);

        $instance = new KeepToolTask($pluginVersion, $toolVersion, $installedVersion);

        self::assertSame(
            'Will keep tool foo in version 1.0.1',
            $instance->getPurposeDescription()
        );
    }
}
