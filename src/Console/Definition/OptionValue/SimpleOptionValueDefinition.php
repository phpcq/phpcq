<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\OptionValue;

final class SimpleOptionValueDefinition extends OptionValueDefinition
{
    /** @var mixed */
    private $defaultValue;

    /** @var string|null */
    private $valueName;

    /** @param mixed $defaultValue */
    public function __construct(bool $required, $defaultValue, ?string $valueName)
    {
        parent::__construct($required);

        $this->defaultValue = $defaultValue;
        $this->valueName = $valueName;
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
