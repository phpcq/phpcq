<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\Builder;

use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\Runner\Console\Definition\OptionDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\KeyValueMapOptionValueDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\OptionParamsDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\OptionValueDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\SimpleOptionValueDefinition;

final class ConsoleOptionBuilder implements ConsoleOptionBuilderInterface
{
    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var string|null */
    private $shortcut;

    /** @var bool */
    private $required = false;

    /** @var bool  */
    private $isArray = false;

    /** @var bool */
    private $isValueRequired = false;

    /** @var array<string|int,mixed> */
    private $optionValues = [];

    /** @var array{defaultValue: mixed, valueSeparator: string}|null */
    private $keyValueMap;

    /** @var string|null */
    private $valueSeparator = null;

    /** @var bool */
    private $onlyShortcut = false;

    public function __construct(string $name, string $description)
    {
        $this->name        = $name;
        $this->description = $description;
    }

    #[\Override]
    public function isRequired(): ConsoleOptionBuilderInterface
    {
        $this->required = true;

        return $this;
    }

    #[\Override]
    public function isArray(): ConsoleOptionBuilderInterface
    {
        $this->isArray = true;

        return $this;
    }

    #[\Override]
    public function withRequiredValue(?string $name = null): ConsoleOptionBuilderInterface
    {
        if (null !== $this->keyValueMap) {
            throw new RuntimeException('Only able to define option values or key value map.');
        }

        $this->isValueRequired = true;
        $this->optionValues[$name ?? -1] = null;

        return $this;
    }

    #[\Override]
    public function withShortcut(string $shortcut): ConsoleOptionBuilderInterface
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    #[\Override]
    public function withOptionalValue(?string $name = null, $defaultValue = null): ConsoleOptionBuilderInterface
    {
        if (null !== $this->keyValueMap) {
            throw new RuntimeException('Only able to define option values or key value map.');
        }

        $this->optionValues[$name ?? -1] = $defaultValue;

        return $this;
    }

    #[\Override]
    public function withKeyValueMap($defaultValue = null, ?string $valueSeparator = null): ConsoleOptionBuilderInterface
    {
        if (count($this->optionValues)) {
            throw new RuntimeException('Only able to define option values or key value map.');
        }

        $this->isValueRequired = true;
        $this->keyValueMap = [
            'defaultValue' => $defaultValue,
            'valueSeparator' => $valueSeparator ?: ConsoleOptionBuilderInterface::VALUE_SEPARATOR_EQUAL_SIGN
        ];

        return $this;
    }

    #[\Override]
    public function withOptionValueSeparator(string $separator): ConsoleOptionBuilderInterface
    {
        $this->valueSeparator = $separator;

        return $this;
    }

    #[\Override]
    public function withShortcutOnly(): ConsoleOptionBuilderInterface
    {
        $this->onlyShortcut = true;

        return $this;
    }

    /** @SuppressWarnings(PHPMD.LongVariable) */
    public function build(string $defaultValueSeparator): OptionDefinition
    {
        return new OptionDefinition(
            $this->name,
            $this->description,
            $this->shortcut,
            $this->required,
            $this->isArray,
            $this->onlyShortcut,
            $this->buildOptionValue(),
            $this->valueSeparator ?: $defaultValueSeparator
        );
    }

    private function buildOptionValue(): ?OptionValueDefinition
    {
        if (count($this->optionValues) === 1) {
            $key = key($this->optionValues);
            return new SimpleOptionValueDefinition(
                $this->isValueRequired,
                current($this->optionValues),
                is_string($key) ? $key : null
            );
        }

        if (count($this->optionValues) > 1) {
            $values = $this->optionValues;
            foreach (array_keys($values) as $key) {
                if (!is_string($key)) {
                    throw new RuntimeException('If defining multiple optional values, the value needs a name');
                }
            }
            /** @psalm-var array<string,mixed> $values */

            return new OptionParamsDefinition($this->isValueRequired, $values);
        }

        if ($this->keyValueMap !== null) {
            return new KeyValueMapOptionValueDefinition(
                $this->isValueRequired,
                $this->keyValueMap['defaultValue'],
                $this->keyValueMap['valueSeparator']
            );
        }

        if ($this->isValueRequired) {
            return new SimpleOptionValueDefinition(true, null, null);
        }

        return null;
    }
}
