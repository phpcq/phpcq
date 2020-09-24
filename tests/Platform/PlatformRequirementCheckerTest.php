<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Platform;

use Phpcq\Runner\Platform\PlatformInformation;
use Phpcq\Runner\Platform\PlatformInformationInterface;
use Phpcq\Runner\Platform\PlatformRequirementChecker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Platform\PlatformRequirementChecker
 */
class PlatformRequirementCheckerTest extends TestCase
{
    public function testChecksAgainstPlatformInformation(): void
    {
        $platform = $this->getMockForAbstractClass(PlatformInformationInterface::class);
        $checker  = PlatformRequirementChecker::create($platform);

        $platform->method('getInstalledVersion')->with('php')->willReturn('1.0.0');

        self::assertTrue($checker->isFulfilled('php', '^1.0.0'));
        self::assertFalse($checker->isFulfilled('php', '^2.0.0'));
    }

    public function currentPlatformProvider(): array
    {
        $platform = PlatformInformation::createFromCurrentPlatform();
        $result = [];
        foreach ($platform->getExtensions() as $extension => $version) {
            $result[] = [$extension, '^' . $version];
        }
        foreach ($platform->getLibraries() as $library => $version) {
            $result[] = [$library, '^' . $version];
        }

        return $result;
    }

    /**
     * @dataProvider currentPlatformProvider
     */
    public function testCreatesCurrentPlatformInformationIfNonPassed(string $name, string $constraint): void
    {
        $checker = PlatformRequirementChecker::create();

        self::assertTrue($checker->isFulfilled($name, $constraint));
    }

    public function testCreateAlwaysFulfillingFulfillsAnything(): void
    {
        $checker = PlatformRequirementChecker::createAlwaysFulfilling();

        self::assertTrue($checker->isFulfilled('php', '^0.0.0'));
        self::assertTrue($checker->isFulfilled('ext-unknown', '^0.0.0'));
    }
}
