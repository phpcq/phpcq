<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\SelfUpdate;

use Phpcq\RepositoryDefinition\VersionRequirementList;
use Phpcq\Runner\SelfUpdate\Version;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\SelfUpdate\Version */
final class VersionTest extends TestCase
{
    public function testGetValues(): void
    {
        $requirements  = $this->createMock(VersionRequirementList::class);
        $version       = "2.0.1";
        $pharFile      = "test.phar";
        $signatureFile = "test.phar.asc";
        $instance      = new Version($version, $requirements, $pharFile, $signatureFile);

        self::assertSame($version, $instance->getVersion());
        self::assertSame($pharFile, $instance->getPharFile());
        self::assertSame($signatureFile, $instance->getSignatureFile());
    }
}
