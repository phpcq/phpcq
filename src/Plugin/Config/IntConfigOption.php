<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use function is_int;

final class IntConfigOption extends AbstractConfigOption
{
    public function __construct(string $name, string $description, int $defaultValue, bool $required)
    {
        parent::__construct($name, $description, $defaultValue, $required);
    }

    public function getType() : string
    {
        return 'int';
    }

    public function validateValue($value) : void
    {
        if (! is_int($value)) {
            $this->throwException($value);
        }
    }
}
