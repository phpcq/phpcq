<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\Builder;

use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\Runner\Console\Definition\OptionDefinition;

/** @SuppressWarnings(PHPMD.LongVariable) */
trait OptionsBuilderTrait
{
    /** @var array<string,ConsoleOptionBuilder> */
    private array $options = [];

    public function describeOption(string $name, string $description): ConsoleOptionBuilderInterface
    {
        if (isset($this->options[$name])) {
            throw new RuntimeException('Option "' . $name . '" already described');
        }

        return $this->options[$name] = new ConsoleOptionBuilder($name, $description);
    }

    /**
     * @return list<OptionDefinition>
     */
    protected function buildOptions(string $defaultValueSeparator): array
    {
        $options = [];

        foreach ($this->options as $option) {
            $options[] = $option->build($defaultValueSeparator);
        }

        return $options;
    }
}
