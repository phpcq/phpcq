<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition;

final class ArgumentDefinition extends AbstractDefinition
{
    /** @var bool */
    private $required;

    /** @var bool */
    private $isArray;

    /** @var mixed */
    private $defaultValue;

    /** @param mixed $defaultValue */
    public function __construct(string $name, string $description, bool $required, bool $isArray, $defaultValue)
    {
        parent::__construct($name, $description);

        $this->required     = $required;
        $this->isArray      = $isArray;
        $this->defaultValue = $defaultValue;
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
