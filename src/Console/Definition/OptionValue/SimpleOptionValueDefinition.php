<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\OptionValue;

final class SimpleOptionValueDefinition extends OptionValueDefinition
{
    /** @param mixed $defaultValue */
    public function __construct(bool $required, private $defaultValue, private readonly ?string $valueName)
    {
        parent::__construct($required);
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return string|null
     */
    public function getValueName(): ?string
    {
        return $this->valueName;
    }
}
