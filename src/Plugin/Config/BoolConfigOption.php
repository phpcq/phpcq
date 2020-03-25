<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use function is_bool;

final class BoolConfigOption extends AbstractConfigOption
{
    public function __construct(string $name, string $description, bool $defaultValue, bool $required)
    {
        parent::__construct($name, $description, $defaultValue, $required);
    }

    public function getType() : string
    {
        return 'bool';
    }

    public function validateValue($value) : void
    {
        if (!is_bool($value)) {
            $this->throwException($value);
        }
    }
}