<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\BoolOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\EnumOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\IntOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\ListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\PrototypeBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

abstract class AbstractOptionsBuilder extends AbstractOptionBuilder implements OptionsBuilderInterface
{
    /** @var ConfigOptionBuilderInterface[]|array<string, ConfigOptionBuilderInterface */
    protected $options = [];

    public function __construct(string $name, string $description)
    {
        parent::__construct($name, $description, [Validator::arrayValidator()]);
    }

    public function describeOptions(string $name, string $description): OptionsBuilderInterface
    {
        $builder = new OptionsBuilder($name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeBoolOption(string $name, string $description): BoolOptionBuilderInterface
    {
        $builder = new BoolOptionBuilder($name, $description, [Validator::boolValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeFloatOption(string $name, string $description): FloatOptionBuilderInterface
    {
        $builder = new FloatOptionBuilder($name, $description, [Validator::floatValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeIntOption(string $name, string $description): IntOptionBuilderInterface
    {
        $builder = new IntOptionBuilder($name, $description, [Validator::intValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describePrototypeOption(string $name, string $description) : PrototypeBuilderInterface
    {
        $builder = new PrototypeOptionBuilder($name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeStringOption(string $name, string $description): StringOptionBuilderInterface
    {
        $builder = new StringOptionBuilder($name, $description, [Validator::stringValidator()]);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeListOption(string $name, string $description): ListOptionBuilderInterface
    {
        $builder = new ListOptionBuilder($name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function describeEnumOption(string $name, string $description): EnumOptionBuilderInterface
    {
        $builder = new EnumOptionBuilder($name, $description);
        $this->describeOption($name, $builder);

        return $builder;
    }

    public function withDefaultValue(array $defaultValue) : OptionsBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function normalizeValue($raw): ?array
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

        foreach ($options as $key => $builder) {
            if (array_key_exists($key, $value) && $value[$key] === null) {
                unset($value[$key]);
                continue;
            }

            if (null === ($processed = $builder->normalizeValue($value[$key] ?? null))) {
                unset($value[$key]);
            } else {
                $value[$key] = $processed;
            }
        }

        return $value;
    }

    public function validateValue($options) : void
    {
        parent::validateValue($options);

        $diff = array_diff_key($options, $this->options);
        if (count($diff) > 0) {
            throw new InvalidConfigurationException(sprintf('Unexpected array keys "%s"', implode(', ', array_keys($diff))));
        }

        assert(is_array($options));
        foreach ($this->options as $key => $builder) {
            $builder->validateValue($options[$key] ?? null);
        }
    }

    protected function describeOption(string $name, OptionBuilderInterface $builder): void
    {
        $this->options[$name] = $builder;
    }
}
