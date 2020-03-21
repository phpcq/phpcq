<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use function is_array;

final class ArrayConfigOption extends AbstractConfigOption
{
    public function __construct(string $name, string $description, array $defaultValue)
    {
        parent::__construct($name, $description, $defaultValue);
    }

    public function getType() : string
    {
        return 'array';
    }

    public function validateValue($value) : void
    {
        if (!is_array($value)) {
            $this->throwException($value);
        }
    }
}
