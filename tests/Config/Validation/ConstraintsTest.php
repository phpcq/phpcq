<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Config\Validation;

use Phpcq\Runner\Config\Validation\Constraints;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Runner\Config\Validation\Constraints */
final class ConstraintsTest extends TestCase
{
    use ConstraintProviderTrait;

    #[DataProvider('boolConstraintProvider')]
    public function testBoolConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Boolean expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::boolConstraint($value));
    }

    #[DataProvider('floatConstraintProvider')]
    public function testFloatConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Float expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::floatConstraint($value));
    }

    #[DataProvider('intConstraintProvider')]
    public function testIntConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Integer expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::intConstraint($value));
    }

    #[DataProvider('arrayConstraintProvider')]
    public function testArrayConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('Array expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::arrayConstraint($value));
    }

    #[DataProvider('stringConstraintProvider')]
    public function testStringConstraint($value, bool $error): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage('String expected, got ' . gettype($value));
        }

        $this->assertSame($value, Constraints::stringConstraint($value));
    }

    #[DataProvider('listConstraintProvider')]
    public function testListConstraint($value, bool $error, int $validator = 0): void
    {
        if ($error) {
            $this->expectException(InvalidConfigurationException::class);
        }

        $itemValidator = null;
        $itemValidatorCalled = 0;

        if ($validator > 0) {
            $itemValidator = static function () use (&$itemValidatorCalled): void {
                $itemValidatorCalled++;
            };
        }

        $this->assertSame($value, Constraints::listConstraint($value, $itemValidator));

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

        $this->assertSame($value, Constraints::enumConstraint($value, $allowed));
    }
}
