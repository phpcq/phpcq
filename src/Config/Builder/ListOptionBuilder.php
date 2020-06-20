<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\ListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;

final class ListOptionBuilder extends AbstractOptionBuilder implements ListOptionBuilderInterface
{
    use TypeTrait;

    /** @var ProcessConfigOptionBuilderInterface|null */
    private $itemBuilder;

    public function ofStringItems() : ListOptionBuilderInterface
    {
        $this->declareType('string');
        $this->withItemValidator(Validator::stringValidator());

        return $this;
    }

    public function ofFloatItems() : ListOptionBuilderInterface
    {
        $this->declareType('float');
        $this->withItemValidator(Validator::floatValidator());

        return $this;
    }

    public function ofIntItems() : ListOptionBuilderInterface
    {
        $this->declareType('int');
        $this->withItemValidator(Validator::intValidator());

        return $this;
    }

    public function ofArrayItems() : OptionsBuilderInterface
    {
        $this->declareType('array');
        $this->withItemValidator(Validator::arrayValidator());

        $builder = new ArrayOptionBuilder('', '');
        $this->itemBuilder = $builder;

        return $builder;
    }

    public function withItemValidator(callable $validator) : OptionBuilderInterface
    {
        $this->withValidator(Validator::listItemValidator($validator));

        return $this;
    }

    protected function getNormalizedValue($raw)
    {
        $values = $raw ?: $this->defaultValue;
        foreach ($this->normalizer as $normalizer) {
            $values = $normalizer($values);
        }

        if (null === $values) {
            return null;
        }

        $values = Constraints::listConstraint($values);
        foreach ($values as $key => $value) {
            $values[$key] = $this->itemBuilder ? $this->itemBuilder->processConfig($value) : $value;
        }

        return $values;
    }

}
