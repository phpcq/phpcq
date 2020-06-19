<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Validation;

use Phpcq\Config\Validation\Constraints;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Config\Validation\Constraints */
final class ConstraintsTest extends TestCase
{
    use ConstraintProviderTrait;

    /** @dataProvider boolConstraintProvider */
    public function testBoolConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectErrorMessage('Boolean expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::boolConstraint($value));
    }

    /** @dataProvider floatConstraintProvider */
    public function testFloatConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectErrorMessage('Float expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::floatConstraint($value));
    }

    /** @dataProvider intConstraintProvider */
    public function testIntConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectErrorMessage('Integer expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::intConstraint($value));
    }

    /** @dataProvider arrayConstraintProvider */
    public function testArrayConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectErrorMessage('Array expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::arrayConstraint($value));
    }

    /** @dataProvider stringConstraintProvider */
    public function testStringConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectErrorMessage('String expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::stringConstraint($value));
    }

    /** @dataProvider listConstraintProvider */
    public function testListConstraint($value, bool $error, int $expectedCallbackInvokeTimes = 0): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
        }

        $itemValidator = null;
        if ($expectedCallbackInvokeTimes > 0) {
            $itemValidatorCalled = 0;
            $itemValidator = static function () use (&$itemValidatorCalled): void {
                $itemValidatorCalled++;
            };
        }

        $this->assertSame($value, Constraints::listConstraint($value, $itemValidator));

        if ($expectedCallbackInvokeTimes > 0) {
            $this->assertEquals(
                $expectedCallbackInvokeTimes,
                $itemValidatorCalled,
                sprintf(
                    'Callback was expected to be called "%s" times, but was called "%s" times',
                    $expectedCallbackInvokeTimes,
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