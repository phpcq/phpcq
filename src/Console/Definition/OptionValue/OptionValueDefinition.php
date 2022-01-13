<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\OptionValue;

abstract class OptionValueDefinition
{
    /** @var bool */
    private $required;

    public function __construct(bool $required)
    {
        $this->required = $required;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
