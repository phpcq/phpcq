<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\PluginApi\Version10\Configuration\Builder\EnumOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\ListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\PrototypeBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

final class PrototypeOptionBuilder extends AbstractOptionBuilder implements PrototypeBuilderInterface
{
    use TypeTrait;

    /** @var ProcessConfigOptionBuilderInterface */
    private $valueBuilder;

    public function ofArrayValue() : OptionsBuilderInterface
    {
        $this->declareType('array');
        $this->valueBuilder = new ArrayOptionBuilder('', '');

        return $this->valueBuilder;
    }

    public function ofBoolValue() : PrototypeBuilderInterface
    {
        $this->declareType('bool');

        return $this;
    }

    public function ofEnumValue() : EnumOptionBuilderInterface
    {
        $this->declareType('enum');
        $this->valueBuilder = new EnumOptionBuilder('', '');

        return $this->valueBuilder;
    }

    public function ofFloatValue() : PrototypeBuilderInterface
    {
        $this->declareType('float');

        return $this;
    }

    public function ofIntValue() : PrototypeBuilderInterface
    {
        $this->declareType('int');

        return $this;
    }

    public function ofListValue(): ListOptionBuilderInterface
    {
        $this->declareType('list');
        $this->valueBuilder = new ListOptionBuilder('', '');

        return $this->valueBuilder;
    }

    public function ofStringValue() : PrototypeBuilderInterface
    {
        $this->declareType('string');

        return $this;
    }

    public function ofPrototypeValue(): PrototypeBuilderInterface
    {
        $this->declareType('prototype');
        $this->valueBuilder = new PrototypeOptionBuilder('', '');

        return $this->valueBuilder;
    }

    public function processConfig($values): ?array
    {
        $values = $this->getNormalizedValue($values);
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
            $values[$key] = $this->valueBuilder->processConfig($value);
        }

        return $values;
    }
}
