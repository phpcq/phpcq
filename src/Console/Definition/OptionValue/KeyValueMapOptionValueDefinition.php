<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\OptionValue;

final class KeyValueMapOptionValueDefinition extends OptionValueDefinition
{
    /** @var mixed */
    private $defaultValue;

    /** @var string */
    private $valueSeparator;

    /** @param mixed $defaultValue */
    public function __construct(bool $required, $defaultValue, string $valueSeparator)
    {
        parent::__construct($required);
        $this->defaultValue = $defaultValue;
        $this->valueSeparator = $valueSeparator;
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
