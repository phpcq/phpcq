<?php

declare(strict_types=1);

namespace Phpcq\Config\Validation;

final class Validator
{
    public static function listItemValidator(callable $validator): callable
    {
        return static function ($value) use ($validator): void {
            Constraints::listConstraint($value, $validator);
        };
    }

    public static function arrayValidator(): callable
    {
        return [Constraints::class, 'arrayConstraint'];
    }

    public static function boolValidator(): callable
    {
        return [Constraints::class, 'boolConstraint'];
    }

    public static function floatValidator(): callable
    {
        return [Constraints::class, 'floatConstraint'];
    }

    public static function intValidator(): callable
    {
        return [Constraints::class, 'intConstraint'];
    }

    public static function stringValidator(): callable
    {
        return [Constraints::class, 'stringConstraint'];
    }

    public static function enumValidator(array $values): callable
    {
        return static function ($value) use ($values): void {
            Constraints::enumConstraint($value, $values);
        };
    }
}