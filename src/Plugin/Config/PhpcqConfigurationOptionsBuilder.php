<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use Phpcq\PluginApi\Version10\ConfigurationOptionInterface;
use Phpcq\PluginApi\Version10\ConfigurationOptionsBuilderInterface;
use Phpcq\PluginApi\Version10\ConfigurationOptionsInterface;

final class PhpcqConfigurationOptionsBuilder implements ConfigurationOptionsBuilderInterface
{
    /** @var array<string, ConfigurationOptionInterface> */
    private $options = [];

    /**
     * {@inheritDoc}
     */
    public function describeArrayOption(
        string $name,
        string $description,
        ?array $defaultValue = null,
        bool $required = false
    ) : ConfigurationOptionsBuilderInterface {
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
    ) : ConfigurationOptionsBuilderInterface {
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
    ) : ConfigurationOptionsBuilderInterface {
        return $this->describeOption(new StringConfigOption($name, $description, $defaultValue, $required));
    }

    public function describeBoolOption(
        string $name,
        string $description,
        ?bool $defaultValue = null,
        bool $required = false
    ) : ConfigurationOptionsBuilderInterface {
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
    ) : ConfigurationOptionsBuilderInterface {
        return $this->describeOption(new FloatConfigOption($name, $description, $defaultValue, $required));
    }

    /**
     * {@inheritDoc}
     */
    public function describeOption(ConfigurationOptionInterface $configOption) : ConfigurationOptionsBuilderInterface
    {
        $this->options[$configOption->getName()] = $configOption;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(): ConfigurationOptionsInterface
    {
        return new ConfigOptions($this->options);
    }
}
