<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\Platform\PlatformRequirementCheckerInterface;
use Phpcq\Repository\Repository;
use Phpcq\Repository\ToolInformationInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Repository\Repository
 */
class RepositoryTest extends TestCase
{
    public function testAddsVersionAndCanRetrieveVersion(): void
    {
        $repository = new Repository();

        $version = $this->createMock(ToolInformationInterface::class);
        $version->method('getVersion')->willReturn('1.0.0');
        $version->method('getName')->willReturn('supertool');

        $repository->addVersion($version);
        $this->assertTrue($repository->hasTool('supertool', '1.0.0'));
        $this->assertTrue($repository->hasTool('supertool', '^1.0.0'));
        $this->assertFalse($repository->hasTool('supertool', '^1.0.1'));
    }

    public function testEnumeratesAllVersions(): void
    {
        $repository = new Repository();

        $version1 = $this->createMock(ToolInformationInterface::class);
        $version1->method('getVersion')->willReturn('1.0.0');
        $version1->method('getName')->willReturn('supertool');

        $version2 = $this->createMock(ToolInformationInterface::class);
        $version2->method('getVersion')->willReturn('1.0.1');
        $version2->method('getName')->willReturn('supertool');

        $repository->addVersion($version1);
        $repository->addVersion($version2);
        $this->assertTrue($repository->hasTool('supertool', '1.0.0'));
        $this->assertTrue($repository->hasTool('supertool', '^1.0.0'));
        $this->assertTrue($repository->hasTool('supertool', '1.0.1'));
        $this->assertTrue($repository->hasTool('supertool', '^1.0.1'));
        $this->assertSame([$version1, $version2], iterator_to_array($repository->getIterator()));
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

        $version1 = $this->createMock(ToolInformationInterface::class);
        $version1->method('getVersion')->willReturn('1.0.0');
        $version1->method('getName')->willReturn('supertool');
        $version1->method('getPlatformRequirements')->willReturn(['php' => '^5.6']);

        $version2 = $this->createMock(ToolInformationInterface::class);
        $version2->method('getVersion')->willReturn('2.0.1');
        $version2->method('getName')->willReturn('supertool');
        $version2->method('getPlatformRequirements')->willReturn(['php' => '^7.1']);

        $repository->addVersion($version1);
        $repository->addVersion($version2);

        $this->assertTrue($repository->hasTool('supertool', '1.0.0'));
        $this->assertTrue($repository->hasTool('supertool', '^1.0.0'));
        $this->assertFalse($repository->hasTool('supertool', '2.0.1'));
        $this->assertFalse($repository->hasTool('supertool', '^2.0.1'));
    }
}