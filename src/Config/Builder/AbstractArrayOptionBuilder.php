<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\EnumOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\ListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\NodeBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\PrototypeBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

abstract class AbstractArrayOptionBuilder extends AbstractOptionBuilder implements OptionsBuilderInterface
{
    /** @var ProcessConfigOptionBuilderInterface[]|array<string, ProcessConfigOptionBuilderInterface */
    protected $options = [];

    public function __construct(NodeBuilderInterface $parent, string $name, string $description)
    {
        parent::__construct($parent, $name, $description, [Validator::arrayValidator()]);
    }

    public function describeArrayOption(string $name, string $description): OptionsBuilderInterface
    {
        $builder = new ArrayOptionBuilder($this, $name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeBoolOption(string $name, string $description): OptionBuilderInterface
    {
        $builder = new OptionBuilder($this, $name, $description, [Validator::boolValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeFloatOption(string $name, string $description): OptionBuilderInterface
    {
        $builder = new OptionBuilder($this, $name, $description, [Validator::floatValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeIntOption(string $name, string $description): OptionBuilderInterface
    {
        $builder = new OptionBuilder($this, $name, $description, [Validator::intValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describePrototype(string $name, string $description) : PrototypeBuilderInterface
    {
        $builder = new PrototypeOptionBuilder($this, $name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeStringOption(string $name, string $description): OptionBuilderInterface
    {
        $builder = new OptionBuilder($this, $name, $description, [Validator::stringValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeListOption(string $name, string $description): ListOptionBuilderInterface
    {
        $builder = new ListOptionBuilder($this, $name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeEnumOption(string $name, string $description): EnumOptionBuilderInterface
    {
        $builder = new EnumOptionBuilder($this, $name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function processConfig($raw): ?array
    {
        $value = $this->getNormalizedValue($raw);
        if ($value === null) {
            if ($this->required) {
                throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
            }

            return null;
        }

        $value = Constraints::arrayConstraint($value);
        $options = $this->options;

        $diff = array_diff_key($value, $options);
        if (count($diff) > 0) {
            throw new InvalidConfigurationException(sprintf('Unexpected array keys "%s"', implode(', ', array_keys($diff))));
        }

        foreach ($options as $key => $builder) {
            $value[$key] = $builder->processConfig($value[$key] ?? null);
        }

        $this->validateValue($value);

        return $value;
    }

    protected function describeOption(string $name, OptionBuilderInterface $builder): void
    {
        $this->options[$name] = $builder;
    }
}