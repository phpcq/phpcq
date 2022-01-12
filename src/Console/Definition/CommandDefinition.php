<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition;

final class CommandDefinition extends AbstractDefinition
{
    /**
     * @var list<ArgumentDefinition>
     */
    private $arguments;

    /**
     * @var list<OptionDefinition>
     */
    private $options;

    /**
     * @param string                   $name
     * @param string                   $description
     * @param list<ArgumentDefinition> $arguments
     * @param list<OptionDefinition>   $options
     */
    public function __construct(string $name, string $description, array $arguments, array $options)
    {
        parent::__construct($name, $description);

        $this->arguments = $arguments;
        $this->options   = $options;
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
