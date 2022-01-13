<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition;

use Phpcq\Runner\Exception\InvalidArgumentException;

final class ApplicationDefinition extends AbstractDefinition
{
    /** @var array<string,CommandDefinition> */
    private $commands = [];

    /** @var list<OptionDefinition> */
    private $options = [];

    /** @var list<ArgumentDefinition> */
    private $arguments = [];

    /** @var string */
    private $optionValueSeparator;

    /**
     * @param string               $name
     * @param string               $description
     * @param CommandDefinition[]  $commands
     * @param ArgumentDefinition[] $arguments ,
     * @param OptionDefinition[]   $options
     * @param string               $optionValueSeparator
     */
    public function __construct(
        string $name,
        string $description,
        array $options,
        array $arguments,
        array $commands,
        string $optionValueSeparator
    ) {
        parent::__construct($name, $description);

        $this->optionValueSeparator = $optionValueSeparator;

        foreach ($commands as $command) {
            $this->commands[$command->getName()] = $command;
        }

        foreach ($arguments as $argument) {
            $this->arguments[] = $argument;
        }

        foreach ($options as $option) {
            $this->options[] = $option;
        }
    }

    /**
     * @return list<CommandDefinition>
     */
    public function getCommands(): array
    {
        return array_values($this->commands);
    }

    public function getCommand(string $name): CommandDefinition
    {
        if (!array_key_exists($name, $this->commands)) {
            throw new InvalidArgumentException('Command "' . $name . '" not found.');
        }

        return $this->commands[$name];
    }

    /** @return list<OptionDefinition> */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return list<ArgumentDefinition>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOptionValueSeparator(): string
    {
        return $this->optionValueSeparator;
    }
}
