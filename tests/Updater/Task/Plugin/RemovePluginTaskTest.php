<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Updater\Task\Plugin\RemovePluginTask;
use Phpcq\Runner\Updater\Task\Tool\RemoveToolTask;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Plugin\RemovePluginTask */
final class RemovePluginTaskTest extends TestCase
{
    public function testDescription(): void
    {
        $pluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $pluginVersion->expects($this->once())->method('getName')->willReturn('foo');
        $pluginVersion->expects($this->once())->method('getVersion')->willReturn('1.0.1');

        $instance = new RemovePluginTask($pluginVersion);

        self::assertSame(
            'Will remove plugin foo in version 1.0.1',
            $instance->getPurposeDescription()
        );
    }
}
