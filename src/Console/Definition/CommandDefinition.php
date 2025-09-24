<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition;

final class CommandDefinition extends AbstractDefinition
{
    /**
     * @param string                   $name
     * @param string                   $description
     * @param list<ArgumentDefinition> $arguments
     * @param list<OptionDefinition>   $options
     */
    public function __construct(
        string $name,
        string $description,
        private readonly array $arguments,
        private readonly array $options
    ) {
        parent::__construct($name, $description);
    }

    /**
     * @return list<ArgumentDefinition>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return list<OptionDefinition>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
