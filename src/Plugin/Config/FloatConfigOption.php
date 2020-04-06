<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use function is_float;

final class FloatConfigOption extends AbstractConfigurationOption
{
    public function __construct(string $name, string $description, ?float $defaultValue, bool $required)
    {
        parent::__construct($name, $description, $defaultValue, $required);
    }

    public function getType() : string
    {
        return 'float';
    }

    public function validateValue($value) : void
    {
        if (is_float($value)) {
            return;
        }

        if ($value === null && !$this->isRequired()) {
            return;
        }

        $this->throwException($value);
    }
}
