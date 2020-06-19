<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\EnumOptionBuilderInterface;

final class EnumOptionBuilder extends AbstractOptionBuilder implements EnumOptionBuilderInterface
{
    use TypeTrait;

    public function ofStringValues(string ...$values) : EnumOptionBuilderInterface
    {
        $this->declareType('string');
        $this
            ->withValidator(Validator::stringValidator())
            ->withValidator(Validator::enumValidator($values));

        return $this;
    }

    public function ofIntValues(int ...$values) : EnumOptionBuilderInterface
    {
        $this->declareType('int');
        $this
            ->withValidator(Validator::intValidator())
            ->withValidator(Validator::enumValidator($values));

        return $this;
    }

    public function ofFloatValues(float ...$values) : EnumOptionBuilderInterface
    {
        $this->declareType('float');
        $this
            ->withValidator(Validator::floatValidator())
            ->withValidator(Validator::enumValidator($values));

        return $this;
    }
}