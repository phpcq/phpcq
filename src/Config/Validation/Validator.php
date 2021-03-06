<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Validation;

/**
 * @psalm-type TValidator = callable(mixed): void
 */
final class Validator
{
    /**
     * @psalm-param TValidator $validator
     * @psalm-return TValidator
     */
    public static function listItemValidator(callable $validator): callable
    {
        return static function ($value) use ($validator): void {
            Constraints::listConstraint($value, $validator);
        };
    }

    /** @psalm-return TValidator */
    public static function arrayValidator(): callable
    {
        return [Constraints::class, 'arrayConstraint'];
    }

    /** @psalm-return TValidator */
    public static function boolValidator(): callable
    {
        return [Constraints::class, 'boolConstraint'];
    }

    /** @psalm-return TValidator */
    public static function floatValidator(): callable
    {
        return [Constraints::class, 'floatConstraint'];
    }

    /** @psalm-return TValidator */
    public static function intValidator(): callable
    {
        return [Constraints::class, 'intConstraint'];
    }

    /** @psalm-return TValidator */
    public static function stringValidator(): callable
    {
        return [Constraints::class, 'stringConstraint'];
    }

    /**
     * @psalm-param list<mixed> $values
     * @psalm-return TValidator
     */
    public static function enumValidator(array $values): callable
    {
        return static function ($value) use ($values): void {
            Constraints::enumConstraint($value, $values);
        };
    }
}
