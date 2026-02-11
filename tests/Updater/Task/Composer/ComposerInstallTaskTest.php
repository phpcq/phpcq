<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Composer;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Updater\Task\Composer\ComposerInstallTask;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Composer\ComposerInstallTask */
final class ComposerInstallTaskTest extends TestCase
{
    public function testDescription(): void
    {
        $pluginVersion = $this->createMock(PluginVersionInterface::class);
        $pluginVersion->expects($this->once())->method('getName')->willReturn('foo');

        $instance = new ComposerInstallTask($pluginVersion);

        self::assertSame(
            'Will install composer dependencies of plugin foo',
            $instance->getPurposeDescription()
        );
    }
}
