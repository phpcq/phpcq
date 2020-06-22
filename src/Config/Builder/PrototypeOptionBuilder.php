<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Configuration\Builder\BoolOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\EnumOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\IntOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\PrototypeBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

final class PrototypeOptionBuilder extends AbstractOptionBuilder implements PrototypeBuilderInterface
{
    use TypeTrait;

    /** @var ConfigOptionBuilderInterface */
    private $valueBuilder;

    public function ofOptionsValue() : OptionsBuilderInterface
    {
        $this->declareType('array');

        return $this->valueBuilder = new OptionsBuilder($this->name, $this->description);
    }

    public function ofBoolValue() : BoolOptionBuilderInterface
    {
        $this->declareType('bool');

        return $this->valueBuilder = new BoolOptionBuilder($this->name, $this->description);
    }

    public function ofEnumValue() : EnumOptionBuilderInterface
    {
        $this->declareType('enum');

        return $this->valueBuilder = new EnumOptionBuilder($this->name, $this->description);
    }

    public function ofFloatValue() : FloatOptionBuilderInterface
    {
        $this->declareType('float');

        return $this->valueBuilder = new FloatOptionBuilder($this->name, $this->description);
    }

    public function ofIntValue() : IntOptionBuilderInterface
    {
        $this->declareType('int');

        return $this->valueBuilder = new IntOptionBuilder($this->name, $this->description);
    }

    public function ofStringListValue(): StringListOptionBuilderInterface
    {
        $this->declareType('string-list');

        return $this->valueBuilder = new StringListOptionBuilder($this->name, $this->description);
    }

    public function ofOptionsListValue(): OptionsListOptionBuilderInterface
    {
        $this->declareType('option-list');

        return $this->valueBuilder = new OptionsListOptionBuilder($this->name, $this->description);
    }

    public function ofStringValue() : StringOptionBuilderInterface
    {
        $this->declareType('string');

        return $this->valueBuilder = new StringOptionBuilder($this->name, $this->description);
    }

    public function ofPrototypeValue(): PrototypeBuilderInterface
    {
        $this->declareType('prototype');

        return $this->valueBuilder = new self($this->name, $this->description);
    }

    public function withDefaultValue(array $defaultValue) : PrototypeBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function selfValidate(): void
    {
        if (null === $this->valueBuilder) {
            throw new RuntimeException('Prototype value type has to be defined');
        }

        $this->valueBuilder->selfValidate();
    }

    public function normalizeValue($values): ?array
    {
        $values = parent::normalizeValue($values);
        if ($values === null) {
            if ($this->required) {
                throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
            }

            return null;
        }

        $values = Constraints::arrayConstraint($values);
        if ($this->required && count($values) === 0) {
            throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
        }

        foreach ($values as $key => $value) {
            $values[$key] = $this->valueBuilder->normalizeValue($value);
        }

        return $values;
    }

    public function validateValue($values): void
    {
        parent::validateValue($values);
        if (null === $values) {
            return;
        }

        $values = Constraints::arrayConstraint($values);
        foreach ($values as $value) {
            $this->valueBuilder->validateValue($value);
        }
    }
}
