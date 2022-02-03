<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Composer;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Updater\Task\Composer\ComposerUpdateTask;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Composer\ComposerUpdateTask */
final class ComposerUpdateTaskTest extends TestCase
{
    public function testDescription(): void
    {
        $pluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $pluginVersion->expects($this->once())->method('getName')->willReturn('foo');

        $instance = new ComposerUpdateTask($pluginVersion);

        self::assertSame(
            'Will update composer dependencies of plugin foo',
            $instance->getPurposeDescription()
        );
    }
}
