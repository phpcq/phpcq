<?php

declare(strict_types=1);

namespace Phpcq\Test\ToolUpdate;

use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\Repository\BootstrapHash;
use Phpcq\Repository\BootstrapInterface;
use Phpcq\Repository\Repository;
use Phpcq\Repository\RepositoryInterface;
use Phpcq\Repository\RepositoryPool;
use Phpcq\Repository\ToolInformationInterface;
use Phpcq\ToolUpdate\UpdateCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\ToolUpdate\UpdateCalculator
 */
final class UpdateCalculatorTest extends TestCase
{
    public function testKeepsAlreadyCurrentTools(): void
    {
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
        $installed = new Repository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.0'],
                ['Will keep foo in version 1.0.0'],
            );

        $bootstrap = $this->getMockForAbstractClass(BootstrapInterface::class);
        $bootstrap->expects(self::atLeastOnce())->method('getHash')->willReturn(
            new BootstrapHash(BootstrapHash::SHA_1, 'foo')
        );
        $tool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $tool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $tool->expects(self::atLeastOnce())->method('getVersion')->willReturn('1.0.0');
        $tool->expects(self::atLeastOnce())->method('getBootstrap')->willReturn($bootstrap);

        $bootstrapOld = $this->getMockForAbstractClass(BootstrapInterface::class);
        $bootstrapOld->expects(self::atLeastOnce())->method('getHash')->willReturn(
            new BootstrapHash(BootstrapHash::SHA_1, 'foo')
        );
        $oldTool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $oldTool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $oldTool->expects(self::atLeastOnce())->method('getVersion')->willReturn('1.0.0');
        $oldTool->expects(self::atLeastOnce())->method('getBootstrap')->willReturn($bootstrapOld);

        $repository->addVersion($tool);
        $installed->addVersion($oldTool);

        $calculator = new UpdateCalculator($installed, $pool, $output);

        $tasks = $calculator->calculate([
            'foo' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        $this->assertSame([
            [
                'type'            => 'keep',
                'tool'            => $oldTool,
                'message'         => 'Will keep foo in version 1.0.0'
            ]
        ], $tasks);
    }

    public function reinstallProvider(): array
    {
        return [
            'keep with identical hashes'    => [
                'newHash' => new BootstrapHash(BootstrapHash::SHA_1, 'foo'),
                'oldHash' => new BootstrapHash(BootstrapHash::SHA_1, 'foo'),
                'keep'    => true,
            ],
            'reinstall if no hashes given'  => [
                'newHash' => null,
                'oldHash' => null,
                'keep'    => false,
            ],
            'reinstall if old hash missing' => [
                'newHash' => new BootstrapHash(BootstrapHash::SHA_1, 'foo'),
                'oldHash' => null,
                'keep'    => false,
            ],
            'reinstall if new hash missing' => [
                'newHash' => null,
                'oldHash' => new BootstrapHash(BootstrapHash::SHA_1, 'foo'),
                'keep'    => false,
            ],
            'reinstall for different hashes' => [
                'newHash' => new BootstrapHash(BootstrapHash::SHA_1, 'foo'),
                'oldHash' => new BootstrapHash(BootstrapHash::SHA_1, 'bar'),
                'keep'    => false,
            ],
        ];
    }

    /**
     * @dataProvider reinstallProvider
     */
    public function testReinstallAlreadyInstalledToolsWithDifferentHash(
        ?BootstrapHash $newHash,
        ?BootstrapHash $oldHash,
        bool $keep
    ): void {
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
        $installed = new Repository();
        $message   = $keep ? 'Will keep foo in version 1.0.0' : 'Will reinstall foo in version 1.0.0';
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.0'],
                [$message],
            );

        $bootstrap = $this->getMockForAbstractClass(BootstrapInterface::class);
        $bootstrap->expects(self::atLeastOnce())->method('getHash')->willReturn($newHash);
        $tool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $tool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $tool->expects(self::atLeastOnce())->method('getVersion')->willReturn('1.0.0');
        $tool->expects(self::atLeastOnce())->method('getBootstrap')->willReturn($bootstrap);

        $bootstrapOld = $this->getMockForAbstractClass(BootstrapInterface::class);
        $bootstrapOld->expects(self::atMost(1))->method('getHash')->willReturn($oldHash);
        $oldTool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $oldTool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $oldTool->expects(self::atLeastOnce())->method('getVersion')->willReturn('1.0.0');
        $oldTool->expects(self::atMost(1))->method('getBootstrap')->willReturn($bootstrapOld);

        $repository->addVersion($tool);
        $installed->addVersion($oldTool);

        $calculator = new UpdateCalculator($installed, $pool, $output);

        $tasks = $calculator->calculate([
            'foo' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        if ($keep) {
            $this->assertSame(
                [
                    [
                        'type'    => 'keep',
                        'tool'    => $oldTool,
                        'message' => $message,
                    ],
                ],
                $tasks
            );
        } else {
            $this->assertSame(
                [
                    [
                        'type'    => 'upgrade',
                        'tool'    => $tool,
                        'old'     => $oldTool,
                        'message' => $message,
                        'signed'  => true
                    ],
                ],
                $tasks
            );
        }
    }

    public function testInstallsMissingTools(): void
    {
        $pool      = new RepositoryPool();
        $pool->addRepository($repository = $this->getMockForAbstractClass(RepositoryInterface::class));
        $installed = $this->getMockForAbstractClass(RepositoryInterface::class);
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.0'],
                ['Will install foo in version 1.0.0'],
            );

        $repository
            ->expects(self::once())
            ->method('hasTool')
            ->with('foo', '^1.0.0')
            ->willReturn(true);
        $repository
            ->expects(self::once())
            ->method('getTool')
            ->with('foo', '^1.0.0')
            ->willReturn($tool = $this->getMockForAbstractClass(ToolInformationInterface::class));
        $tool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $tool->expects(self::atLeastOnce())->method('getVersion')->willReturn('1.0.0');

        $calculator = new UpdateCalculator($installed, $pool, $output);

        $tasks = $calculator->calculate([
            'foo' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        $this->assertSame([
            [
                'type'    => 'install',
                'tool'    => $tool,
                'message' => 'Will install foo in version 1.0.0',
                'signed'  => true,
            ]
        ], $tasks);
    }

    public function testUpgradesOutdatedTools(): void
    {
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
        $installed = new Repository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.1'],
                ['Will upgrade foo from version 1.0.0 to version 1.0.1'],
            );

        $tool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $tool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $tool->expects(self::atLeastOnce())->method('getVersion')->willReturn('1.0.1');

        $oldTool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $oldTool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $oldTool->expects(self::atLeastOnce())->method('getVersion')->willReturn('1.0.0');

        $repository->addVersion($tool);
        $installed->addVersion($oldTool);

        $calculator = new UpdateCalculator($installed, $pool, $output);

        $tasks = $calculator->calculate([
            'foo' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        $this->assertSame([
            [
                'type'    => 'upgrade',
                'tool'    => $tool,
                'old'     => $oldTool,
                'message' => 'Will upgrade foo from version 1.0.0 to version 1.0.1',
                'signed'  => true,
            ]
        ], $tasks);
    }

    public function testDowngradesTools(): void
    {
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
        $installed = new Repository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 1.0.1'],
                ['Will downgrade foo from version 2.0.0 to version 1.0.1'],
            );

        $tool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $tool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $tool->expects(self::atLeastOnce())->method('getVersion')->willReturn('1.0.1');

        $oldTool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $oldTool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $oldTool->expects(self::atLeastOnce())->method('getVersion')->willReturn('2.0.0');

        $repository->addVersion($tool);
        $installed->addVersion($oldTool);

        $calculator = new UpdateCalculator($installed, $pool, $output);

        $tasks = $calculator->calculate([
            'foo' => ['version' => '^1.0.0', 'signed' => true]
        ]);

        $this->assertSame([
            [
                'type'    => 'upgrade',
                'tool'    => $tool,
                'old'     => $oldTool,
                'message' => 'Will downgrade foo from version 2.0.0 to version 1.0.1',
                'signed'  => true,
            ]
        ], $tasks);
    }

    public function testRemovesTools(): void
    {
        $pool = new RepositoryPool();
        $installed = new Repository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Will remove foo version 2.0.0'],
            );

        $oldTool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $oldTool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $oldTool->expects(self::atLeastOnce())->method('getVersion')->willReturn('2.0.0');

        $installed->addVersion($oldTool);

        $calculator = new UpdateCalculator($installed, $pool, $output);

        $tasks = $calculator->calculate([]);

        $this->assertSame([
            [
                'type'    => 'remove',
                'tool'    => $oldTool,
                'message' => 'Will remove foo version 2.0.0',
            ]
        ], $tasks);
    }


    public function testReinstallTool(): void
    {
        $pool = new RepositoryPool();
        $pool->addRepository($repository = new Repository());
        $installed = new Repository();
        $output    = $this->getMockForAbstractClass(OutputInterface::class);

        $output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Want foo in version 2.0.0'],
                ['Will reinstall foo in version 2.0.0'],
            );

        $tool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $tool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $tool->expects(self::atLeastOnce())->method('getVersion')->willReturn('2.0.0');

        $oldTool = $this->getMockForAbstractClass(ToolInformationInterface::class);
        $oldTool->expects(self::atLeastOnce())->method('getName')->willReturn('foo');
        $oldTool->expects(self::atLeastOnce())->method('getVersion')->willReturn('2.0.0');

        $repository->addVersion($tool);
        $installed->addVersion($oldTool);

        $calculator = new UpdateCalculator($installed, $pool, $output);

        $tasks = $calculator->calculate(
            ['foo' => ['version' => '^2.0.0', 'signed' => true]],
            true
        );

        $this->assertSame([
            [
                'type'    => 'upgrade',
                'tool'    => $tool,
                'old'     => $oldTool,
                'message' => 'Will reinstall foo in version 2.0.0',
                'signed'  => true,
            ]
        ], $tasks);
    }
}
