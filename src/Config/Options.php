<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config;

use Phpcq\Runner\Config\Validation\Constraints;
use Phpcq\Runner\Config\Validation\Validator;
use Phpcq\Runner\Exception\InvalidArgumentException;
use Phpcq\PluginApi\Version10\Configuration\OptionsInterface;

class Options implements OptionsInterface
{
    /** @param array<string, mixed> $options */
    public function __construct(private array $options)
    {
    }

    #[\Override]
    public function getInt(string $name): int
    {
        return Constraints::intConstraint($this->getOption($name));
    }

    #[\Override]
    public function getString(string $name): string
    {
        return Constraints::stringConstraint($this->getOption($name));
    }

    #[\Override]
    public function getFloat(string $name): float
    {
        return Constraints::floatConstraint($this->getOption($name));
    }

    #[\Override]
    public function getBool(string $name): bool
    {
        return Constraints::boolConstraint($this->getOption($name));
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-return list<string>
     */
    #[\Override]
    public function getStringList(string $name): array
    {
        return Constraints::listConstraint($this->getOption($name), Validator::stringValidator());
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-return list<array<string,mixed>>
     */
    #[\Override]
    public function getOptionsList(string $name): array
    {
        return Constraints::listConstraint($this->getOption($name), Validator::arrayValidator());
    }

    #[\Override]
    public function getOptions(string $name): array
    {
        $value = Constraints::arrayConstraint($this->getOption($name));
        /** @psalm-var array<string,mixed> $value */
        return $value;
    }

    #[\Override]
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    #[\Override]
    public function getValue(): array
    {
        return $this->options;
    }

    /** @return mixed */
    protected function getOption(string $name)
    {
        if (!isset($this->options[$name])) {
            throw new InvalidArgumentException(sprintf('Unknown configuration key "%s"', $name));
        }

        return $this->options[$name];
    }
}
