<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\OptionValue;

final class KeyValueMapOptionValueDefinition extends OptionValueDefinition
{
    /** @param mixed $defaultValue */
    public function __construct(bool $required, private $defaultValue, private readonly string $valueSeparator)
    {
        parent::__construct($required);
    }

    /** @return mixed */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /** @return string */
    public function getValueSeparator(): string
    {
        return $this->valueSeparator;
    }
}
