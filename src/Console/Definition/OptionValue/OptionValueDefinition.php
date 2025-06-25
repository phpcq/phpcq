<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\OptionValue;

abstract class OptionValueDefinition
{
    public function __construct(private readonly bool $required)
    {
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
