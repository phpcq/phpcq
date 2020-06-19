<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\PluginApi\Version10\Configuration\Builder\EnumOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\ListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\PrototypeBuilderInterface;

final class PrototypeOptionBuilder extends AbstractOptionBuilder implements PrototypeBuilderInterface
{
    use TypeTrait;

    /** @var OptionBuilderInterface */
    private $valueBuilder;

    public function ofArrayValue() : OptionsBuilderInterface
    {
        $this->declareType('array');
        $this->valueBuilder = new ArrayOptionBuilder($this, '', '');

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
        $this->valueBuilder = new EnumOptionBuilder($this, '', '');

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
        $this->valueBuilder = new ListOptionBuilder($this, '', '');

        return $this->valueBuilder;
    }

    public function ofStringValue() : PrototypeBuilderInterface
    {
        $this->declareType('string');

        return $this;
    }
}