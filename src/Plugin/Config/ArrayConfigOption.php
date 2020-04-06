<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use function is_array;

final class ArrayConfigOption extends AbstractConfigurationOption
{
    public function __construct(string $name, string $description, ?array $defaultValue, bool $required)
    {
        parent::__construct($name, $description, $defaultValue, $required);
    }

    public function getType() : string
    {
        return 'array';
    }

    public function validateValue($value) : void
    {
        if (is_array($value)) {
            return;
        }

        if ($value === null && !$this->isRequired()) {
            return;
        }

        $this->throwException($value);
    }
}
