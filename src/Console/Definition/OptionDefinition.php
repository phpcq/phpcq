<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition;

use Phpcq\Runner\Console\Definition\OptionValue\OptionValueDefinition;

final class OptionDefinition extends AbstractDefinition
{
    public function __construct(
        string $name,
        string $description,
        private readonly ?string $shortcut,
        private readonly bool $required,
        private readonly bool $isArray,
        private readonly bool $onlyShortcut,
        private readonly ?OptionValueDefinition $optionValue,
        private readonly string $valueSeparator
    ) {
        parent::__construct($name, $description);
    }

    public function getShortcut(): ?string
    {
        if ($this->isOnlyShortcut()) {
            return $this->getName();
        }

        return $this->shortcut;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }

    public function isOnlyShortcut(): bool
    {
        return $this->onlyShortcut;
    }

    /**
     * @return OptionValueDefinition|null
     */
    public function getOptionValue(): ?OptionValueDefinition
    {
        return $this->optionValue;
    }

    public function getValueSeparator(): string
    {
        return $this->valueSeparator;
    }
}
