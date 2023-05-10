<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Validation;

use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

use function get_class;
use function gettype;
use function is_object;

final class Constraints
{
    /** @psalm-param mixed $value */
    public static function boolConstraint($value): bool
    {
        if (!is_bool($value)) {
            throw new InvalidConfigurationException('Boolean expected, got ' . gettype($value));
        }

        return $value;
    }

    /** @psalm-param mixed $value */
    public static function floatConstraint($value): float
    {
        if (!is_float($value)) {
            throw new InvalidConfigurationException('Float expected, got ' . gettype($value));
        }

        return $value;
    }

    /** @psalm-param mixed $value */
    public static function intConstraint($value): int
    {
        if (!is_int($value)) {
            throw new InvalidConfigurationException('Integer expected, got ' . gettype($value));
        }

        return $value;
    }

    /** @psalm-param mixed $value */
    public static function arrayConstraint($value): array
    {
        if (!is_array($value)) {
            throw new InvalidConfigurationException('Array expected, got ' . gettype($value));
        }

        return $value;
    }

    /** @psalm-param mixed $value */
    public static function stringConstraint($value): string
    {
        if (!is_string($value)) {
            throw new InvalidConfigurationException('String expected, got ' . gettype($value));
        }

        return $value;
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-param mixed $value
     * @psalm-return list<mixed>
     */
    public static function listConstraint($value, ?callable $itemValidator = null): array
    {
        $value    = self::arrayConstraint($value);
        $expected = 0;

        foreach (array_keys($value) as $index) {
            if ($index !== $expected) {
                throw new InvalidConfigurationException(
                    sprintf('List item key "%s" expected, got "%s"', $expected, $index)
                );
            }

            if (null !== $itemValidator) {
                $itemValidator($value[$index]);
            }
            $expected++;
        }

        /** @psalm-var list<mixed> $value */
        return $value;
    }

    /**
     * @param mixed $value
     * @psalm-param list<mixed> $acceptedValues
     * @psalm-return mixed
     */
    public static function enumConstraint($value, array $acceptedValues)
    {
        if (!in_array($value, $acceptedValues, true)) {
            throw new InvalidConfigurationException('Unexpected value given');
        }

        return $value;
    }
}
