<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Composer;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Updater\Task\Composer\RemoveComposerDependenciesTask;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Composer\RemoveComposerDependenciesTask */
final class RemoveComposerDependenciesTaskTest extends TestCase
{
    public function testDescription(): void
    {
        $pluginVersion = $this->createMock(PluginVersionInterface::class);
        $pluginVersion->expects($this->once())->method('getName')->willReturn('foo');

        $instance = new RemoveComposerDependenciesTask($pluginVersion);

        self::assertSame(
            'Will remove composer dependencies of plugin foo',
            $instance->getPurposeDescription()
        );
    }
}
