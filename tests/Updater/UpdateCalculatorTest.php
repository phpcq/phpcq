<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Runner\Updater;

use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirement;
use Phpcq\Runner\Repository\BuiltInPlugin;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Resolver\ResolverInterface;
use Phpcq\Runner\Updater\UpdateCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Updater\UpdateCalculator
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class UpdateCalculatorTest extends TestCase
{
    public function testKeepsAlreadyCurrentTools(): void
    {
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $installedToolVersion = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $installedToolVersion->method('getName')->willReturn('tool');
        $installedToolVersion->method('getVersion')->willReturn('2.0.0');

        $pluginRequirements  = new PluginRequirements();
        $pluginRequirements->getToolRequirements()->add(new VersionRequirement('tool', '^2.0.0'));
        $pluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $pluginVersion->method('getName')->willReturn('plugin');
        $pluginVersion->method('getVersion')->willReturn('1.0.0');
        $pluginVersion->method('getRequirements')->willReturn($pluginRequirements);

        $installedPlugin = new InstalledPlugin($pluginVersion, ['tool' => $installedToolVersion]);

        $installed->addPlugin($installedPlugin);
        $installed->addToolVersion($installedToolVersion);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want plugin in version 1.0.0'],
                ['Will keep tool tool in version 2.0.0'],
                ['Will keep foo in version 1.0.0'],
            );

        $desiredToolVersion = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $desiredToolVersion->method('getName')->willReturn('tool');
        $desiredToolVersion->method('getVersion')->willReturn('2.0.0');
        $desiredToolVersion->method('getRequirements')->willReturn(new ToolRequirements());
        $desiredToolVersion->method('getHash')->willReturn(null);

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolvePluginVersion')
            ->with('plugin', '^1.0.0')
            ->willReturn($pluginVersion);
        $resolver
            ->expects($this->once())
            ->method('resolveToolVersion')
            ->with('plugin', 'tool', '^2.0.0')
            ->willReturn($desiredToolVersion);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate([
            'plugin' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        $this->assertSame([
            [
                'type'            => 'keep',
                'plugin'          => $installedPlugin,
                'version'         => $pluginVersion,
                'message'         => 'Will keep plugin plugin in version 1.0.0',
                'tasks'           => [
                    [
                        'type'      => 'keep',
                        'tool'      => $desiredToolVersion,
                        'installed' => $installedToolVersion,
                        'message'   => 'Will keep tool tool in version 2.0.0',
                    ],
                ],
            ]
        ], $tasks);
    }

    public function reinstallPluginForHashChangeProvider(): array
    {
        return [
            'keep with identical hashes'    => [
                'newHash' => PluginHash::create(PluginHash::SHA_1, 'foo'),
                'oldHash' => PluginHash::create(PluginHash::SHA_1, 'foo'),
                'keep'    => true,
            ],
            'reinstall for different hashes' => [
                'newHash' => PluginHash::create(PluginHash::SHA_1, 'foo'),
                'oldHash' => PluginHash::create(PluginHash::SHA_1, 'bar'),
                'keep'    => false,
            ],
            'reinstall for different hash algorithm' => [
                'newHash' => PluginHash::create(PluginHash::SHA_512, 'foo'),
                'oldHash' => PluginHash::create(PluginHash::SHA_1, 'bar'),
                'keep'    => false,
            ],
        ];
    }

    /**
     * @dataProvider reinstallPluginForHashChangeProvider
     */
    public function testReinstallPluginsHashMatcher(?PluginHash $newHash, ?PluginHash $oldHash, bool $keep): void
    {
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);
        $message   = $keep ? 'Will keep plugin foo in version 1.0.0' : 'Will reinstall plugin foo in version 1.0.0';
        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.0'],
                [$message],
            );

        $installedVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedVersion->method('getName')->willReturn('foo');
        $installedVersion->method('getVersion')->willReturn('1.0.0');
        $installedVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedVersion->method('getHash')->willReturn($oldHash);
        $installedPlugin = new InstalledPlugin($installedVersion);

        $desiredVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $desiredVersion->method('getName')->willReturn('foo');
        $desiredVersion->method('getVersion')->willReturn('1.0.0');
        $desiredVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $desiredVersion->method('getHash')->willReturn($newHash);

        $installed->addPlugin($installedPlugin);

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolvePluginVersion')
            ->with('foo', '^1.0.0')
            ->willReturn($desiredVersion);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate([
            'foo' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        if ($keep) {
            $this->assertSame(
                [
                    [
                        'type'            => 'keep',
                        'plugin'          => $installedPlugin,
                        'version'         => $desiredVersion,
                        'message'         => $message,
                        'tasks'           => []
                    ],
                ],
                $tasks
            );
        } else {
            $this->assertSame(
                [
                    [
                        'type'            => 'upgrade',
                        'version'         => $desiredVersion,
                        'old'             => $installedVersion,
                        'message'         => $message,
                        'signed'          => true,
                        'tasks'           => []
                    ],
                ],
                $tasks
            );
        }
    }

    public function reinstallToolForHashChangeProvider(): array
    {
        return [
            'keep with identical hashes'    => [
                'newHash' => ToolHash::create(ToolHash::SHA_1, 'foo'),
                'oldHash' => ToolHash::create(ToolHash::SHA_1, 'foo'),
                'keep'    => true,
            ],
            'reinstall if no hashes given'  => [
                'newHash' => null,
                'oldHash' => null,
                'keep'    => true,
            ],
            'reinstall if old hash missing' => [
                'newHash' => ToolHash::create(ToolHash::SHA_1, 'foo'),
                'oldHash' => null,
                'keep'    => false,
            ],
            'keep if new hash missing (we can not determine if repository has removed hash)' => [
                'newHash' => null,
                'oldHash' => ToolHash::create(ToolHash::SHA_1, 'foo'),
                'keep'    => true,
            ],
            'reinstall for different hashes' => [
                'newHash' => ToolHash::create(ToolHash::SHA_1, 'foo'),
                'oldHash' => ToolHash::create(ToolHash::SHA_1, 'bar'),
                'keep'    => false,
            ],
            'reinstall for different hash algorithm' => [
                'newHash' => ToolHash::create(ToolHash::SHA_512, 'foo'),
                'oldHash' => ToolHash::create(ToolHash::SHA_1, 'bar'),
                'keep'    => false,
            ],
        ];
    }

    /**
     * @dataProvider reinstallToolForHashChangeProvider
     */
    public function testReinstallToolHashMatcher(?ToolHash $newHash, ?ToolHash $oldHash, bool $keep): void
    {
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);
        $message   = $keep ? 'Will keep tool bar in version 2.0.0' : 'Will reinstall tool bar in version 2.0.0';
        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.0'],
                [$message],
            );

        $installedToolVersion = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $installedToolVersion->method('getName')->willReturn('bar');
        $installedToolVersion->method('getVersion')->willReturn('2.0.0');
        $installedToolVersion->method('getHash')->willReturn($oldHash);

        $pluginRequirements  = new PluginRequirements();
        $pluginRequirements->getToolRequirements()->add(new VersionRequirement('bar', '^2.0.0'));
        $pluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $pluginVersion->method('getName')->willReturn('foo');
        $pluginVersion->method('getVersion')->willReturn('1.0.0');
        $pluginVersion->method('getRequirements')->willReturn($pluginRequirements);
        $installedPlugin = new InstalledPlugin($pluginVersion, ['bar' => $installedToolVersion]);

        $desiredToolVersion = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $desiredToolVersion->method('getName')->willReturn('bar');
        $desiredToolVersion->method('getVersion')->willReturn('2.0.0');
        $desiredToolVersion->method('getRequirements')->willReturn(new ToolRequirements());
        $desiredToolVersion->method('getHash')->willReturn($newHash);

        $installed->addPlugin($installedPlugin);
        $installed->addToolVersion($installedToolVersion);

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolvePluginVersion')
            ->with('foo', '^1.0.0')
            ->willReturn($pluginVersion);
        $resolver
            ->expects($this->once())
            ->method('resolveToolVersion')
            ->with('foo', 'bar', '^2.0.0')
            ->willReturn($desiredToolVersion);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate([
            'foo' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        if ($keep) {
            $this->assertSame(
                [
                    [
                        'type'            => 'keep',
                        'plugin'          => $installedPlugin,
                        'version'         => $pluginVersion,
                        'message'         => 'Will keep plugin foo in version 1.0.0',
                        'tasks'           => [
                            [
                                'type'      => 'keep',
                                'tool'      => $desiredToolVersion,
                                'installed' => $installedToolVersion,
                                'message'   => $message,
                            ],
                        ]
                    ],
                ],
                $tasks
            );
        } else {
            $this->assertSame(
                [
                    [
                        'type'            => 'keep',
                        'plugin'          => $installedPlugin,
                        'version'         => $pluginVersion,
                        'message'         => 'Will keep plugin foo in version 1.0.0',
                        'tasks'           => [
                            [
                                'type'    => 'upgrade',
                                'tool'    => $desiredToolVersion,
                                'message' => $message,
                                'old'     => $installedToolVersion,
                                'signed'  => true,
                            ],
                        ]
                    ],
                ],
                $tasks
            );
        }
    }

    public function testInstallsMissingPlugins(): void
    {
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.0'],
                ['Will install plugin foo in version 1.0.0'],
            );

        $desiredPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $desiredPluginVersion->method('getName')->willReturn('foo');
        $desiredPluginVersion->method('getVersion')->willReturn('1.0.0');
        $desiredPluginVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $desiredPluginVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'hash'));

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolvePluginVersion')
            ->with('foo', '^1.0.0')
            ->willReturn($desiredPluginVersion);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate([
            'foo' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        $this->assertSame([
            [
                'type'    => 'install',
                'version' => $desiredPluginVersion,
                'message' => 'Will install plugin foo in version 1.0.0',
                'signed'  => true,
                'tasks'   => [],
            ]
        ], $tasks);
    }

    public function testUpgradesOutdatedPlugins(): void
    {
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.1'],
                ['Will upgrade plugin foo from version 1.0.0 to version 1.0.1'],
            );

        $installedVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedVersion->method('getName')->willReturn('foo');
        $installedVersion->method('getVersion')->willReturn('1.0.0');
        $installedVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'old-hash'));
        $installedPlugin = new InstalledPlugin($installedVersion);
        $installed->addPlugin($installedPlugin);

        $desiredVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $desiredVersion->method('getName')->willReturn('foo');
        $desiredVersion->method('getVersion')->willReturn('1.0.1');
        $desiredVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $desiredVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'new-hash'));

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolvePluginVersion')
            ->with('foo', '^1.0.0')
            ->willReturn($desiredVersion);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate([
            'foo' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        $this->assertSame([
            [
                'type'    => 'upgrade',
                'version' => $desiredVersion,
                'old'     => $installedVersion,
                'message' => 'Will upgrade plugin foo from version 1.0.0 to version 1.0.1',
                'signed'  => true,
                'tasks'   => [],
            ]
        ], $tasks);
    }

    public function testDowngradesPlugins(): void
    {
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.1'],
                ['Will downgrade plugin foo from version 2.0.0 to version 1.0.1'],
            );

        $installedVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedVersion->method('getName')->willReturn('foo');
        $installedVersion->method('getVersion')->willReturn('2.0.0');
        $installedVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'old-hash'));
        $installedPlugin = new InstalledPlugin($installedVersion);
        $installed->addPlugin($installedPlugin);

        $desiredVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $desiredVersion->method('getName')->willReturn('foo');
        $desiredVersion->method('getVersion')->willReturn('1.0.1');
        $desiredVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $desiredVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'new-hash'));

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolvePluginVersion')
            ->with('foo', '^1.0.0')
            ->willReturn($desiredVersion);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate([
            'foo' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        $this->assertSame([
            [
                'type'    => 'upgrade',
                'version' => $desiredVersion,
                'old'     => $installedVersion,
                'message' => 'Will downgrade plugin foo from version 2.0.0 to version 1.0.1',
                'signed'  => true,
                'tasks'   => [],
            ]
        ], $tasks);
    }

    public function testRemovesPlugins(): void
    {
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Will remove plugin foo version 2.0.0'],
            );

        $installedVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedVersion->method('getName')->willReturn('foo');
        $installedVersion->method('getVersion')->willReturn('2.0.0');
        $installedVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'old-hash'));
        $installedPlugin = new InstalledPlugin($installedVersion);
        $installed->addPlugin($installedPlugin);

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate([]);

        $this->assertSame([
            [
                'type'    => 'remove',
                'plugin'  => $installedPlugin,
                'version' => $installedVersion,
                'message' => 'Will remove plugin foo version 2.0.0',
            ]
        ], $tasks);
    }

    public function testReinstallPlugins(): void
    {
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.0'],
                ['Will reinstall plugin foo in version 1.0.0'],
            );

        $installedVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedVersion->method('getName')->willReturn('foo');
        $installedVersion->method('getVersion')->willReturn('1.0.0');
        $installedVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'hash'));
        $installedPlugin = new InstalledPlugin($installedVersion);
        $installed->addPlugin($installedPlugin);

        $desiredVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $desiredVersion->method('getName')->willReturn('foo');
        $desiredVersion->method('getVersion')->willReturn('1.0.0');
        $desiredVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $desiredVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'hash'));

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolvePluginVersion')
            ->with('foo', '^1.0.0')
            ->willReturn($desiredVersion);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate(
            [
                'foo' => ['version' => '^1.0.0', 'signed' => true]
            ],
            true
        );

        $this->assertSame([
            [
                'type'    => 'upgrade',
                'version' => $desiredVersion,
                'old'     => $installedVersion,
                'message' => 'Will reinstall plugin foo in version 1.0.0',
                'signed'  => true,
                'tasks'   => [],
            ]
        ], $tasks);
    }

    public function testIgnoresBuiltInPlugins(): void
    {
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::never())
            ->method('writeln');

        $installedVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedVersion->method('getName')->willReturn('foo');
        $installedVersion->method('getVersion')->willReturn('2.0.0');
        $installedVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'old-hash'));
        $installedPlugin = new BuiltInPlugin($installedVersion);
        $installed->addPlugin($installedPlugin);

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate([]);
        $this->assertSame([], $tasks);
    }
}
