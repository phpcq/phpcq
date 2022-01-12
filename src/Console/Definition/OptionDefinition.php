<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition;

use Phpcq\Runner\Console\Definition\OptionValue\OptionValueDefinition;

final class OptionDefinition extends AbstractDefinition
{
    /**
     * @var string|null
     */
    private $shortcut;

    /**
     * @var bool
     */
    private $required;

    /**
     * @var bool
     */
    private $isArray;

    /**
     * @var OptionValueDefinition|null
     */
    private $optionValue;

    /**
     * @var string
     */
    private $valueSeparator;

    /** @var bool */
    private $onlyShortcut;

    public function __construct(
        string $name,
        string $description,
        ?string $shortcut,
        bool $required,
        bool $isArray,
        bool $onlyShortcut,
        ?OptionValueDefinition $optionValue,
        string $valueSeparator
    ) {
        parent::__construct($name, $description);

        $this->shortcut = $shortcut;
        $this->required = $required;
        $this->isArray = $isArray;
        $this->optionValue = $optionValue;
        $this->valueSeparator = $valueSeparator;
        $this->onlyShortcut = $onlyShortcut;
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
