<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\Builder;

use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleArgumentBuilderInterface;
use Phpcq\Runner\Console\Definition\ArgumentDefinition;

class ConsoleArgumentBuilder implements ConsoleArgumentBuilderInterface
{
    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var bool */
    private $isArray = false;

    /** @var bool */
    private $required = false;

    /** @var mixed */
    private $defaultValue;

    public function __construct(string $name, string $description)
    {
        $this->name        = $name;
        $this->description = $description;
    }

    #[\Override]
    public function isRequired(): ConsoleArgumentBuilderInterface
    {
        $this->required = true;

        return $this;
    }

    #[\Override]
    public function isArray(): ConsoleArgumentBuilderInterface
    {
        $this->isArray = true;

        return $this;
    }

    #[\Override]
    public function withDefaultValue($value): ConsoleArgumentBuilderInterface
    {
        $this->defaultValue = $value;

        return $this;
    }

    public function build(): ArgumentDefinition
    {
        return new ArgumentDefinition(
            $this->name,
            $this->description,
            $this->required,
            $this->isArray,
            $this->defaultValue
        );
    }
}
