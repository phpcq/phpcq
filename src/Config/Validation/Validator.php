<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Validation;

/**
 * @psalm-type TValidator = callable(mixed): void
 */
final class Validator
{
    /**
     * @param TValidator $validator
     * @return TValidator
     */
    public static function listItemValidator(callable $validator): callable
    {
        return static function ($value) use ($validator): void {
            Constraints::listConstraint($value, $validator);
        };
    }

    /** @return TValidator */
    public static function arrayValidator(): callable
    {
        return Constraints::arrayConstraint(...);
    }

    /** @return TValidator */
    public static function boolValidator(): callable
    {
        return Constraints::boolConstraint(...);
    }

    /** @return TValidator */
    public static function floatValidator(): callable
    {
        return Constraints::floatConstraint(...);
    }

    /** @return TValidator */
    public static function intValidator(): callable
    {
        return Constraints::intConstraint(...);
    }

    /** @return TValidator */
    public static function stringValidator(): callable
    {
        return Constraints::stringConstraint(...);
    }

    /**
     * @param list<mixed> $values
     * @return TValidator
     */
    public static function enumValidator(array $values): callable
    {
        return static function ($value) use ($values): void {
            Constraints::enumConstraint($value, $values);
        };
    }
}
