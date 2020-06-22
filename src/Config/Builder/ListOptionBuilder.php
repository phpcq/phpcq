<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\IntOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\ListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringOptionBuilderInterface;

final class ListOptionBuilder extends AbstractOptionBuilder implements ListOptionBuilderInterface
{
    use TypeTrait;

    /** @var ConfigOptionBuilderInterface */
    private $itemBuilder;

    public function ofStringItems() : StringOptionBuilderInterface
    {
        $this->declareType('string');
        $this->withItemValidator(Validator::stringValidator());

        return $this->itemBuilder = new StringOptionBuilder($this->name, $this->description);
    }

    public function ofFloatItems() : FloatOptionBuilderInterface
    {
        $this->declareType('float');
        $this->withItemValidator(Validator::floatValidator());

        return $this->itemBuilder = new FloatOptionBuilder($this->name, $this->description);
    }

    public function ofIntItems() : IntOptionBuilderInterface
    {
        $this->declareType('int');
        $this->withItemValidator(Validator::intValidator());

        return $this->itemBuilder = new IntOptionBuilder($this->name, $this->description);
    }

    public function ofOptionsItems() : OptionsBuilderInterface
    {
        $this->declareType('array');
        $this->withItemValidator(Validator::arrayValidator());

        return $this->itemBuilder = new OptionsBuilder($this->name, $this->description);
    }

    public function withItemValidator(callable $validator) : OptionBuilderInterface
    {
        $this->withValidator(Validator::listItemValidator($validator));

        return $this;
    }

    public function withDefaultValue(array $defaultValue) : ListOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function selfValidate() : void
    {
        if (null === $this->itemBuilder) {
            throw new RuntimeException('List type not defined');
        }

        $this->itemBuilder->selfValidate();
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
            $values[$key] = $this->itemBuilder ? $this->itemBuilder->normalizeValue($value) : $value;
        }

        return $values;
    }

}
