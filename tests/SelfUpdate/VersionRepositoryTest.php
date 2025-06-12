<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\SelfUpdate;

use Phpcq\RepositoryDefinition\VersionRequirementList;
use Phpcq\Runner\Platform\PlatformRequirementChecker;
use Phpcq\Runner\SelfUpdate\Version;
use Phpcq\Runner\SelfUpdate\VersionsRepository;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\SelfUpdate\VersionRepository */
final class VersionRepositoryTest extends TestCase
{
    public function testAddVersion(): void
    {
        $repository = new VersionsRepository(PlatformRequirementChecker::createAlwaysFulfilling());
        $version = new Version('1.0.0', new VersionRequirementList(), 'test.phar', 'test.phar.asc');
        $repository->addVersion($version);

        self::assertSame($version, $repository->findMatchingVersion());
    }
}
