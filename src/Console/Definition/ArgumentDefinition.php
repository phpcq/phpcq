<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition;

final class ArgumentDefinition extends AbstractDefinition
{
    /** @param mixed $defaultValue */
    public function __construct(
        string $name,
        string $description,
        private readonly bool $required,
        private readonly bool $isArray,
        private $defaultValue
    ) {
        parent::__construct($name, $description);
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }

    /** @return mixed */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
