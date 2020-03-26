<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

final class PhpcqConfigOptionsBuilder implements ConfigOptionsBuilderInterface
{
    /** @var array<string, ConfigOptionInterface> */
    private $options = [];

    /**
     * {@inheritDoc}
     */
    public function describeArrayOption(
        string $name,
        string $description,
        ?array $defaultValue = null,
        bool $required = false
    ) : ConfigOptionsBuilderInterface {
        return $this->describeOption(new ArrayConfigOption($name, $description, $defaultValue, $required));
    }

    /**
     * {@inheritDoc}
     */
    public function describeIntOption(
        string $name,
        string $description,
        ?int $defaultValue = null,
        bool $required = false
    ) : ConfigOptionsBuilderInterface {
        return $this->describeOption(new IntConfigOption($name, $description, $defaultValue, $required));
    }

    /**
     * {@inheritDoc}
     */
    public function describeStringOption(
        string $name,
        string $description,
        ?string $defaultValue = null,
        bool $required = false
    ) : ConfigOptionsBuilderInterface {
        return $this->describeOption(new StringConfigOption($name, $description, $defaultValue, $required));
    }

    public function describeBoolOption(
        string $name,
        string $description,
        ?bool $defaultValue = null,
        bool $required = false
    ) : ConfigOptionsBuilderInterface {
        return $this->describeOption(new BoolConfigOption($name, $description, $defaultValue, $required));
    }

    /**
     * {@inheritDoc}
     */
    public function describeFloatOption(
        string $name,
        string $description,
        ?float $defaultValue = null,
        bool $required = false
    ) : ConfigOptionsBuilderInterface {
        return $this->describeOption(new FloatConfigOption($name, $description, $defaultValue, $required));
    }

    /**
     * {@inheritDoc}
     */
    public function describeOption(ConfigOptionInterface $configOption) : ConfigOptionsBuilderInterface
    {
        $this->options[$configOption->getName()] = $configOption;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(): ConfigOptions
    {
        return new ConfigOptions($this->options);
    }
}
