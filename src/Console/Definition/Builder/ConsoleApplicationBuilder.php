<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\Builder;

use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleApplicationBuilderInterface;
use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleCommandBuilderInterface;
use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\Runner\Console\Definition\ApplicationDefinition;

class ConsoleApplicationBuilder implements ConsoleApplicationBuilderInterface
{
    use ArgumentsBuilderTrait;
    use OptionsBuilderTrait;

    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var array<string,ConsoleCommandBuilder> */
    private $commands = [];

    /** @var string */
    private $optionValueSeparator = ConsoleOptionBuilderInterface::VALUE_SEPARATOR_EQUAL_SIGN;

    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function withOptionValueSeparator(string $separator): ConsoleApplicationBuilderInterface
    {
        $this->optionValueSeparator = $separator;

        return $this;
    }

    public function describeCommand(string $name, string $description): ConsoleCommandBuilderInterface
    {
        if (isset($this->commands[$name])) {
            throw new RuntimeException('Command "' . $name . '" already described');
        }

        return $this->commands[$name] = new ConsoleCommandBuilder($name, $description);
    }

    public function build(): ApplicationDefinition
    {
        $commands = [];
        foreach ($this->commands as $command) {
            $commands[] = $command->build($this->optionValueSeparator);
        }

        return new ApplicationDefinition(
            $this->name,
            $this->description,
            $this->buildOptions($this->optionValueSeparator),
            $this->buildArguments(),
            $commands,
            $this->optionValueSeparator
        );
    }
}
