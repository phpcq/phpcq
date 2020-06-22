<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Validator;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Configuration\Builder\EnumOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\IntOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringOptionBuilderInterface;

final class EnumOptionBuilder extends AbstractOptionBuilder implements EnumOptionBuilderInterface
{
    use TypeTrait;

    /** @var ConfigOptionBuilderInterface */
    private $valueBuilder;

    public function ofStringValues(string ...$values) : StringOptionBuilderInterface
    {
        $this->declareType('string');
        $this
            ->withValidator(Validator::stringValidator())
            ->withValidator(Validator::enumValidator($values));

        return $this->valueBuilder = new StringOptionBuilder($this->name, $this->description);
    }

    public function ofIntValues(int ...$values) : IntOptionBuilderInterface
    {
        $this->declareType('int');
        $this
            ->withValidator(Validator::intValidator())
            ->withValidator(Validator::enumValidator($values));

        return $this->valueBuilder = new IntOptionBuilder($this->name, $this->description);
    }

    public function ofFloatValues(float ...$values) : FloatOptionBuilderInterface
    {
        $this->declareType('float');
        $this
            ->withValidator(Validator::floatValidator())
            ->withValidator(Validator::enumValidator($values));

        return $this->valueBuilder = new FloatOptionBuilder($this->name, $this->description);
    }

    public function normalizeValue($raw)
    {
        $raw = $this->valueBuilder->normalizeValue($raw);

        return parent::normalizeValue($raw);
    }

    public function validateValue($value) : void
    {
        parent::validateValue($value);

        $this->valueBuilder->normalizeValue($value);
    }

    public function selfValidate() : void
    {
        if (null === $this->valueBuilder) {
            throw new RuntimeException('Enum value type has to be defined');
        }
    }
}
