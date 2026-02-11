<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Validation;

use Phpcq\Runner\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Config\Validation\Validator */
final class ValidatorTest extends TestCase
{
    use ConstraintProviderTrait;

    #[DataProvider('boolConstraintProvider')]
    public function testBoolConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Boolean expected, got ' . gettype($value));
        }

        $validator = Validator::boolValidator();
        $this->assertIsCallable($validator);
        $validator($value);
    }

    #[DataProvider('floatConstraintProvider')]
    public function testFloatConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Float expected, got ' . gettype($value));
        }

        $validator = Validator::floatValidator();
        $this->assertIsCallable($validator);
        $validator($value);
    }

    #[DataProvider('intConstraintProvider')]
    public function testIntConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Integer expected, got ' . gettype($value));
        }

        $validator = Validator::intValidator();
        $this->assertIsCallable($validator);
        $validator($value);
    }

    #[DataProvider('arrayConstraintProvider')]
    public function testArrayConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Array expected, got ' . gettype($value));
        }

        $validator = Validator::arrayValidator();
        $this->assertIsCallable($validator);
        $validator($value);
    }

    #[DataProvider('stringConstraintProvider')]
    public function testStringConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('String expected, got ' . gettype($value));
        }

        $validator = Validator::stringValidator();
        $this->assertIsCallable($validator);
        $validator($value);
    }

    #[DataProvider('listConstraintProvider')]
    public function testListItemValidator($value, bool $error, int $validator = 0): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
        }

        $itemValidatorCalled = 0;
        $itemValidator = static function () use (&$itemValidatorCalled): void {
            $itemValidatorCalled++;
        };

        $itemValidator = Validator::listItemValidator($itemValidator);
        $this->assertIsCallable($itemValidator);
        $itemValidator($value);

        if ($validator > 0) {
            $this->assertEquals(
                $validator,
                $itemValidatorCalled,
                sprintf(
                    'Callback was expected to be called "%s" times, but was called "%s" times',
                    $validator,
                    $itemValidatorCalled
                )
            );
        }
    }

    #[DataProvider('enumConstraintProvider')]
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
