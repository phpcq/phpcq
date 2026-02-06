<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Plugin;

use Generator;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Updater\Task\Plugin\UpgradePluginTask;
use Phpcq\Runner\Updater\Task\Tool\UpgradeToolTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Plugin\UpgradePluginTask */
final class UpgradePluginTaskTest extends TestCase
{
    #[DataProvider('descriptionTestProvider')]
    public function testDescription(string $description, string $desired, string $installed): void
    {
        $pluginVersion = $this->createMock(PluginVersionInterface::class);
        $pluginVersion->expects($this->atLeastOnce())->method('getName')->willReturn('foo');
        $pluginVersion->expects($this->atLeastOnce())->method('getVersion')->willReturn($desired);

        $installedVersion = $this->createMock(PluginVersionInterface::class);
        $installedVersion->expects($this->atLeastOnce())->method('getVersion')->willReturn($installed);

        $instance = new UpgradePluginTask($pluginVersion, $installedVersion, true);

        self::assertSame(
            $description,
            $instance->getPurposeDescription()
        );
    }

    public static function descriptionTestProvider(): Generator
    {
        yield 'Test upgrade description' => [
            'description' => 'Will upgrade plugin foo from version 1.0.0 to version 1.0.1',
            'desired'   => '1.0.1',
            'installed' => '1.0.0',
        ];

        yield 'Test downgrade description' => [
            'description' => 'Will downgrade plugin foo from version 1.0.1 to version 1.0.0',
            'desired'   => '1.0.0',
            'installed' => '1.0.1',
        ];

        yield 'Test reinstall description' => [
            'description' => 'Will reinstall plugin foo in version 1.0.1',
            'desired'   => '1.0.1',
            'installed' => '1.0.1',
        ];
    }
}
