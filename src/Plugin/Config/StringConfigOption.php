<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use function is_string;

final class StringConfigOption extends AbstractConfigurationOption
{
    public function __construct(string $name, string $description, ?string $defaultValue, bool $required)
    {
        parent::__construct($name, $description, $defaultValue, $required);
    }

    public function getType() : string
    {
        return 'string';
    }

    public function validateValue($value) : void
    {
        if (is_string($value)) {
            return;
        }

        if ($value === null && !$this->isRequired()) {
            return;
        }

        $this->throwException($value);
    }
}
