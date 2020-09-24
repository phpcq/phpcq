<?php

declare(strict_types=1);

namespace Phpcq\Test\Runner\Updater;

use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirement;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\Repository;
use Phpcq\Runner\Repository\RepositoryPool;
use Phpcq\Runner\Resolver\ResolverInterface;
use Phpcq\Runner\Updater\UpdateCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Updater\UpdateCalculator
 */
final class UpdateCalculatorTest extends TestCase
{
    public function testKeepsAlreadyCurrentTools(): void
    {
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $installedToolVersion = $this->getMockForAbstractClass(ToolVersionInterface::class);
        $installedToolVersion->method('getName')->willReturn('tool');
        $installedToolVersion->method('getVersion')->willReturn('2.0.0');

        $installedRequirements  = new PluginRequirements();
        $installedRequirements->getToolRequirements()->add(new VersionRequirement('tool', '^2.0.0'));
        $installedPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedPluginVersion->method('getName')->willReturn('plugin');
        $installedPluginVersion->method('getVersion')->willReturn('1.0.0');
        $installedPluginVersion->method('getRequirements')->willReturn($installedRequirements);

        $installedPlugin = new InstalledPlugin($installedPluginVersion, ['tool' => $installedToolVersion]);

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

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolvePluginVersion')
            ->with('plugin', '^1.0.0')
            ->willReturn($installedPluginVersion);
        $resolver
            ->expects($this->once())
            ->method('resolveToolVersion')
            ->with('plugin', 'tool', '^2.0.0')
            ->willReturn($installedToolVersion);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate([
            'plugin' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        $this->assertSame([
            [
                'type'            => 'keep',
                'plugin'          => $installedPlugin,
                'version'         => $installedPluginVersion,
                'message'         => 'Will keep plugin plugin in version 1.0.0',
                'tasks'           => [
                    [
                        'type'    => 'keep',
                        'tool'    => $installedToolVersion,
                        'message' => 'Will keep tool tool in version 2.0.0',
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
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
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

        $installedPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedPluginVersion->method('getName')->willReturn('foo');
        $installedPluginVersion->method('getVersion')->willReturn('1.0.0');
        $installedPluginVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedPluginVersion->method('getHash')->willReturn($oldHash);
        $installedPlugin = new InstalledPlugin($installedPluginVersion);

        $desiredPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $desiredPluginVersion->method('getName')->willReturn('foo');
        $desiredPluginVersion->method('getVersion')->willReturn('1.0.0');
        $desiredPluginVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $desiredPluginVersion->method('getHash')->willReturn($newHash);

        $installed->addPlugin($installedPlugin);

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

        if ($keep) {
            $this->assertSame(
                [
                    [
                        'type'            => 'keep',
                        'plugin'          => $installedPlugin,
                        'version'         => $installedPluginVersion,
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
                        'version'         => $desiredPluginVersion,
                        'old'             => $installedPluginVersion,
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
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
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

        $installedRequirements  = new PluginRequirements();
        $installedRequirements->getToolRequirements()->add(new VersionRequirement('bar', '^2.0.0'));
        $installedPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedPluginVersion->method('getName')->willReturn('foo');
        $installedPluginVersion->method('getVersion')->willReturn('1.0.0');
        $installedPluginVersion->method('getRequirements')->willReturn($installedRequirements);
        $installedPlugin = new InstalledPlugin($installedPluginVersion, ['bar' => $installedToolVersion]);

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
            ->willReturn($installedPluginVersion);
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
                        'version'         => $installedPluginVersion,
                        'message'         => 'Will keep plugin foo in version 1.0.0',
                        'tasks'           => [
                            [
                                'type'    => 'keep',
                                'tool'    => $installedToolVersion,
                                'message' => $message,
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
                        'version'         => $installedPluginVersion,
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
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
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
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.1'],
                ['Will upgrade plugin foo from version 1.0.0 to version 1.0.1'],
            );

        $installedPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedPluginVersion->method('getName')->willReturn('foo');
        $installedPluginVersion->method('getVersion')->willReturn('1.0.0');
        $installedPluginVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedPluginVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'old-hash'));
        $installedPlugin = new InstalledPlugin($installedPluginVersion);
        $installed->addPlugin($installedPlugin);

        $desiredPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $desiredPluginVersion->method('getName')->willReturn('foo');
        $desiredPluginVersion->method('getVersion')->willReturn('1.0.1');
        $desiredPluginVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $desiredPluginVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'new-hash'));

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
                'type'    => 'upgrade',
                'version' => $desiredPluginVersion,
                'old'     => $installedPluginVersion,
                'message' => 'Will upgrade plugin foo from version 1.0.0 to version 1.0.1',
                'signed'  => true,
                'tasks'   => [],
            ]
        ], $tasks);
    }

    public function testDowngradesPlugins(): void
    {
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.1'],
                ['Will downgrade plugin foo from version 2.0.0 to version 1.0.1'],
            );

        $installedPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedPluginVersion->method('getName')->willReturn('foo');
        $installedPluginVersion->method('getVersion')->willReturn('2.0.0');
        $installedPluginVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedPluginVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'old-hash'));
        $installedPlugin = new InstalledPlugin($installedPluginVersion);
        $installed->addPlugin($installedPlugin);

        $desiredPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $desiredPluginVersion->method('getName')->willReturn('foo');
        $desiredPluginVersion->method('getVersion')->willReturn('1.0.1');
        $desiredPluginVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $desiredPluginVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'new-hash'));

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
                'type'    => 'upgrade',
                'version' => $desiredPluginVersion,
                'old'     => $installedPluginVersion,
                'message' => 'Will downgrade plugin foo from version 2.0.0 to version 1.0.1',
                'signed'  => true,
                'tasks'   => [],
            ]
        ], $tasks);
    }

    public function testRemovesPlugins(): void
    {
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Will remove plugin foo version 2.0.0'],
            );

        $installedPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedPluginVersion->method('getName')->willReturn('foo');
        $installedPluginVersion->method('getVersion')->willReturn('2.0.0');
        $installedPluginVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedPluginVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'old-hash'));
        $installedPlugin = new InstalledPlugin($installedPluginVersion);
        $installed->addPlugin($installedPlugin);

        $resolver = $this->getMockForAbstractClass(ResolverInterface::class);

        $calculator = new UpdateCalculator($installed, $resolver, $output);

        $tasks = $calculator->calculate([]);

        $this->assertSame([
            [
                'type'    => 'remove',
                'plugin'  => $installedPlugin,
                'version' => $installedPluginVersion,
                'message' => 'Will remove plugin foo version 2.0.0',
            ]
        ], $tasks);
    }

    public function testReinstallPlugins(): void
    {
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
        $installed = new InstalledRepository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.0'],
                ['Will reinstall plugin foo in version 1.0.0'],
            );

        $installedPluginVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $installedPluginVersion->method('getName')->willReturn('foo');
        $installedPluginVersion->method('getVersion')->willReturn('1.0.0');
        $installedPluginVersion->method('getRequirements')->willReturn(new PluginRequirements());
        $installedPluginVersion->method('getHash')->willReturn(PluginHash::create(PluginHash::SHA_1, 'hash'));
        $installedPlugin = new InstalledPlugin($installedPluginVersion);
        $installed->addPlugin($installedPlugin);

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

        $tasks = $calculator->calculate(
            [
                'foo' => ['version' => '^1.0.0', 'signed' => true]
            ],
            true
        );

        $this->assertSame([
            [
                'type'    => 'upgrade',
                'version' => $desiredPluginVersion,
                'old'     => $installedPluginVersion,
                'message' => 'Will reinstall plugin foo in version 1.0.0',
                'signed'  => true,
                'tasks'   => [],
            ]
        ], $tasks);
    }
}
