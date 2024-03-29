<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Validation;

use Phpcq\Runner\Config\Validation\Constraints;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Config\Validation\Constraints */
final class ConstraintsTest extends TestCase
{
    use ConstraintProviderTrait;

    /** @dataProvider boolConstraintProvider */
    public function testBoolConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Boolean expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::boolConstraint($value));
    }

    /** @dataProvider floatConstraintProvider */
    public function testFloatConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Float expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::floatConstraint($value));
    }

    /** @dataProvider intConstraintProvider */
    public function testIntConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Integer expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::intConstraint($value));
    }

    /** @dataProvider arrayConstraintProvider */
    public function testArrayConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Array expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::arrayConstraint($value));
    }

    /** @dataProvider stringConstraintProvider */
    public function testStringConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('String expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::stringConstraint($value));
    }

    /** @dataProvider listConstraintProvider */
    public function testListConstraint($value, bool $error, int $expectedCalls = 0): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
        }

        $itemValidator = null;
        if ($expectedCalls > 0) {
            $itemValidatorCalled = 0;
            $itemValidator = static function () use (&$itemValidatorCalled): void {
                $itemValidatorCalled++;
            };
        }

        $this->assertSame($value, Constraints::listConstraint($value, $itemValidator));

        if ($expectedCalls > 0) {
            $this->assertEquals(
                $expectedCalls,
                $itemValidatorCalled,
                sprintf(
                    'Callback was expected to be called "%s" times, but was called "%s" times',
                    $expectedCalls,
                    $itemValidatorCalled
                )
            );
        }
    }

    /** @dataProvider enumConstraintProvider */
    public function testEnumConstraint($value, array $allowed, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
        }

        $this->assertSame($value, Constraints::enumConstraint($value, $allowed));
    }
}
