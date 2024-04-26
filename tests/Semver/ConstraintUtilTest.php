<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Semver;

use Phpcq\Runner\Semver\ConstraintUtil;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Semver\ConstraintUtil */
final class ConstraintUtilTest extends TestCase
{
    public function testMatches(): void
    {
        $this->assertTrue(
            ConstraintUtil::matches('7.4', '7.4'),
            'Identical requirements should match'
        );

        $this->assertTrue(
            ConstraintUtil::matches('7.4.1', '>=7.4'),
            'Requirement at the beginning of a range should match'
        );

        $this->assertTrue(
            ConstraintUtil::matches('^7.4', '^7.4 || ^8.0'),
            'Requirement included by a constraint should match'
        );

        $this->assertFalse(
            ConstraintUtil::matches('7.4', '<7.3'),
            'Requirement outside of a range should not match'
        );

        $this->assertFalse(
            ConstraintUtil::matches('8.0', '7.4'),
            'Requirement greater than defined version should not match'
        );
    }
}
