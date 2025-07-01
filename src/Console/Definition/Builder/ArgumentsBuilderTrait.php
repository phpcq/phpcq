<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\Builder;

use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleArgumentBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\Runner\Console\Definition\ArgumentDefinition;

trait ArgumentsBuilderTrait
{
    /** @var array<string,ConsoleArgumentBuilder> */
    private array $arguments = [];

    public function describeArgument(string $name, string $description): ConsoleArgumentBuilderInterface
    {
        if (isset($this->arguments[$name])) {
            throw new RuntimeException('Argument "' . $name . '" already described');
        }

        return $this->arguments[$name] = new ConsoleArgumentBuilder($name, $description);
    }

    /** @return list<ArgumentDefinition> */
    protected function buildArguments(): array
    {
        $arguments = [];

        foreach ($this->arguments as $argument) {
            $arguments[] = $argument->build();
        }

        return $arguments;
    }
}
