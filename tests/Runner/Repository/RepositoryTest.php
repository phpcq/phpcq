<?php

declare(strict_types=1);

namespace Phpcq\Test\Runner\Repository;

use ArrayIterator;
use Phpcq\Platform\PlatformRequirementCheckerInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirement;
use Phpcq\RepositoryDefinition\VersionRequirementList;
use Phpcq\Runner\Repository\Repository;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Repository\Repository
 */
class RepositoryTest extends TestCase
{
    public function testAddsPluginVersionAndCanRetrieveVersion(): void
    {
        $repository = new Repository();

        $pluginRequirements = $this->createMock(PluginRequirements::class);
        $pluginRequirements->method('getPhpRequirements')->willReturn($this->createMock(VersionRequirementList::class));

        $version = $this->createMock(PluginVersionInterface::class);
        $version->method('getVersion')->willReturn('1.0.0');
        $version->method('getName')->willReturn('supertool');
        $version->method('getRequirements')->willReturn($pluginRequirements);

        $repository->addPluginVersion($version);
        $this->assertTrue($repository->hasPluginVersion('supertool', '1.0.0'));
        $this->assertTrue($repository->hasPluginVersion('supertool', '^1.0.0'));
        $this->assertFalse($repository->hasPluginVersion('supertool', '^1.0.1'));
    }

    public function testEnumeratesAllVersions(): void
    {
        $repository = new Repository();

        $pluginRequirements = $this->createMock(PluginRequirements::class);
        $pluginRequirements->method('getPhpRequirements')->willReturn($this->createMock(VersionRequirementList::class));

        $version1 = $this->createMock(PluginVersionInterface::class);
        $version1->method('getVersion')->willReturn('1.0.0');
        $version1->method('getName')->willReturn('supertool');
        $version1->method('getRequirements')->willReturn($pluginRequirements);

        $version2 = $this->createMock(PluginVersionInterface::class);
        $version2->method('getVersion')->willReturn('1.0.1');
        $version2->method('getName')->willReturn('supertool');
        $version2->method('getRequirements')->willReturn($pluginRequirements);

        $repository->addPluginVersion($version1);
        $repository->addPluginVersion($version2);
        $this->assertTrue($repository->hasPluginVersion('supertool', '1.0.0'));
        $this->assertTrue($repository->hasPluginVersion('supertool', '^1.0.0'));
        $this->assertTrue($repository->hasPluginVersion('supertool', '1.0.1'));
        $this->assertTrue($repository->hasPluginVersion('supertool', '^1.0.1'));
        $this->assertSame([$version1, $version2], iterator_to_array($repository->iteratePluginVersions()));
    }

    public function testAppliedPlatformInformation(): void
    {
        $platformInformation = $this->createMock(PlatformRequirementCheckerInterface::class);
        $platformInformation
            ->method('isFulfilled')
            ->willReturnCallback(function (string $name, string $constraint): bool {
                if ('php' !== $name) {
                    return false;
                }

                return $constraint === '^5.6';
            });

        $repository = new Repository($platformInformation);

        $phpRequirement = $this->createMock(VersionRequirement::class);
        $phpRequirement->method('getName')->willReturn('php');
        $phpRequirement->method('getConstraint')->willReturn('^5.6');

        $phpRequirements = $this->createMock(VersionRequirementList::class);
        $phpRequirements->method('get')->with('php')->willReturn($phpRequirement);
        $phpRequirements->method('getIterator')->willReturn(new ArrayIterator([$phpRequirement]));

        $requirements = $this->createMock(PluginRequirements::class);
        $requirements->method('getPhpRequirements')->willReturn($phpRequirements);

        $version1 = $this->createMock(PluginVersionInterface::class);
        $version1->method('getVersion')->willReturn('1.0.0');
        $version1->method('getName')->willReturn('supertool');
        $version1->method('getRequirements')->willReturn($requirements);

        $phpRequirement = $this->createMock(VersionRequirement::class);
        $phpRequirement->method('getName')->willReturn('php');
        $phpRequirement->method('getConstraint')->willReturn('^7.1');

        $phpRequirements = $this->createMock(VersionRequirementList::class);
        $phpRequirements->method('get')->with('php')->willReturn($phpRequirement);
        $phpRequirements->method('getIterator')->willReturn(new ArrayIterator([$phpRequirement]));

        $requirements = $this->createMock(PluginRequirements::class);
        $requirements->method('getPhpRequirements')->willReturn($phpRequirements);

        $version2 = $this->createMock(PluginVersionInterface::class);
        $version2->method('getVersion')->willReturn('2.0.1');
        $version2->method('getName')->willReturn('supertool');
        $version2->method('getRequirements')->willReturn($requirements);

        $repository->addPluginVersion($version1);
        $repository->addPluginVersion($version2);

        $this->assertTrue($repository->hasPluginVersion('supertool', '1.0.0'));
        $this->assertTrue($repository->hasPluginVersion('supertool', '^1.0.0'));
        $this->assertFalse($repository->hasPluginVersion('supertool', '2.0.1'));
        $this->assertFalse($repository->hasPluginVersion('supertool', '^2.0.1'));
    }
}
