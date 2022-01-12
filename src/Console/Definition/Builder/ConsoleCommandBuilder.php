<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\Builder;

use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleArgumentBuilderInterface;
use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleCommandBuilderInterface;
use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\Runner\Console\Definition\CommandDefinition;

final class ConsoleCommandBuilder implements ConsoleCommandBuilderInterface
{
    use ArgumentsBuilderTrait;
    use OptionsBuilderTrait;

    /** @var string */
    private $name;

    /** @var string */
    private $description;

    public function __construct(string $name, string $description)
    {
        $this->name        = $name;
        $this->description = $description;
    }

    /** @SuppressWarnings(PHPMD.LongVariable) */
    public function build(string $defaultValueSeparator): CommandDefinition
    {
        return new CommandDefinition(
            $this->name,
            $this->description,
            $this->buildArguments(),
            $this->buildOptions($defaultValueSeparator)
        );
    }
}
