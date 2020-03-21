<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use function is_string;

final class StringConfigOption extends AbstractConfigOption
{
    public function __construct(string $name, string $description, string $defaultValue)
    {
        parent::__construct($name, $description, $defaultValue);
    }

    public function getType() : string
    {
        return 'string';
    }

    public function validateValue($value) : void
    {
        if (!is_string($value)) {
            $this->throwException($value);
        }
    }
}
