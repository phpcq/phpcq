<?php

declare(strict_types=1);

namespace Phpcq\Config;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\Exception\InvalidArgumentException;
use Phpcq\PluginApi\Version10\Configuration\OptionsInterface;

class Options implements OptionsInterface
{
    /** @var array<string, mixed> */
    private $options;

    /** @param array<string, mixed> $options */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getInt(string $name): int
    {
        return Constraints::intConstraint($this->getOption($name));
    }

    public function getString(string $name): string
    {
        return Constraints::stringConstraint($this->getOption($name));
    }

    public function getFloat(string $name): float
    {
        return Constraints::floatConstraint($this->getOption($name));
    }

    public function getBool(string $name) : bool
    {
        return Constraints::boolConstraint($this->getOption($name));
    }

    public function getList(string $name): array
    {
        return Constraints::listConstraint($this->getOption($name));
    }

    public function getStringList(string $name): array
    {
        return Constraints::listConstraint($this->getOption($name), Validator::stringValidator());
    }

    public function getOptions(string $name): OptionsInterface
    {
        $value = Constraints::arrayConstraint($this->getOption($name));
        return new Options($value);
    }

    public function has(string $name) : bool
    {
        return array_key_exists($name, $this->options);
    }

    public function getValue() : array
    {
        return $this->options;
    }

    protected function getOption(string $name)
    {
        if (!isset($this->options[$name])) {
            throw new InvalidArgumentException(sprintf('Unknown configuration key "%s"', $name));
        }

        return $this->options[$name];
    }
}
