<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Updater\Task\Tool;

use Generator;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Updater\Task\Tool\UpgradeToolTask;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Updater\Task\Tool\UpgradeToolTask */
final class UpgradeToolTaskTest extends TestCase
{
    /** @dataProvider descriptionTestProvider  */
    public function testDescription(string $description, string $desired, string $installed): void
    {
        $pluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $toolVersion = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $toolVersion->expects($this->atLeastOnce())->method('getName')->willReturn('foo');
        $toolVersion->expects($this->atLeastOnce())->method('getVersion')->willReturn($desired);

        $oldToolVersion = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $oldToolVersion->expects($this->atLeastOnce())->method('getVersion')->willReturn($installed);

        $instance = new UpgradeToolTask($pluginVersion, $toolVersion, $oldToolVersion, true);

        self::assertSame(
            $description,
            $instance->getPurposeDescription()
        );
    }

    public function descriptionTestProvider(): Generator
    {
        yield 'Test upgrade description' => [
            'description' => 'Will upgrade tool foo from version 1.0.0 to version 1.0.1',
            'desired'   => '1.0.1',
            'installed' => '1.0.0',
        ];

        yield 'Test downgrade description' => [
            'description' => 'Will downgrade tool foo from version 1.0.1 to version 1.0.0',
            'desired'   => '1.0.0',
            'installed' => '1.0.1',
        ];

        yield 'Test reinstall description' => [
            'description' => 'Will reinstall tool foo in version 1.0.1',
            'desired'   => '1.0.1',
            'installed' => '1.0.1',
        ];
    }
}
