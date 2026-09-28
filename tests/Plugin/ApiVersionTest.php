<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Plugin;

use Phpcq\Runner\Plugin\ApiVersion;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Plugin\ApiVersion */
final class ApiVersionTest extends TestCase
{
    public function testRangeMatchesConstants(): void
    {
        $range = ApiVersion::range();

        self::assertSame(ApiVersion::MIN, $range->getMin());
        self::assertSame(ApiVersion::MAX, $range->getMax());
        self::assertTrue($range->contains('1.0.0'));
    }

    public function testSupportsApi11AndApi10Plugins(): void
    {
        $range = ApiVersion::range();

        self::assertTrue($range->contains('1.0.0'));
        self::assertTrue($range->contains('1.1.0'));
        self::assertFalse($range->contains('1.2.0'));
    }
}
