<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Validation;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Config\Validation\Validator */
final class ValidatorTest extends TestCase
{
    use ConstraintProviderTrait;

    /** @dataProvider boolConstraintProvider */
    public function testBoolConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectErrorMessage('Boolean expected, got ' . gettype($value));
        }

        $validator = Validator::boolValidator();
        $this->assertIsCallable($validator);
        $validator($value);
    }

    /** @dataProvider floatConstraintProvider */
    public function testFloatConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectErrorMessage('Float expected, got ' . gettype($value));
        }

        $validator = Validator::floatValidator();
        $this->assertIsCallable($validator);
        $validator($value);
    }

    /** @dataProvider intConstraintProvider */
    public function testIntConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectErrorMessage('Integer expected, got ' . gettype($value));
        }

        $validator = Validator::intValidator();
        $this->assertIsCallable($validator);
        $validator($value);
    }

    /** @dataProvider arrayConstraintProvider */
    public function testArrayConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectErrorMessage('Array expected, got ' . gettype($value));
        }

        $validator = Validator::arrayValidator();
        $this->assertIsCallable($validator);
        $validator($value);
    }

    /** @dataProvider stringConstraintProvider */
    public function testStringConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectErrorMessage('String expected, got ' . gettype($value));
        }

        $validator = Validator::stringValidator();
        $this->assertIsCallable($validator);
        $validator($value);
    }

    /** @dataProvider listConstraintProvider */
    public function testListItemValidator($value, bool $error, int $expectedCalls = 0): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
        }

        $itemValidatorCalled = 0;
        $itemValidator = static function () use (&$itemValidatorCalled): void {
            $itemValidatorCalled++;
        };

        $validator = Validator::listItemValidator($itemValidator);
        $this->assertIsCallable($validator);
        $validator($value);

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

        $validator = Validator::enumValidator($allowed);
        $this->assertIsCallable($validator);
        $validator($value);
    }
}
