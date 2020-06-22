<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;

final class FloatOptionBuilder extends AbstractOptionBuilder implements FloatOptionBuilderInterface
{
    public function __construct(string $name, string $description, array $validators = [])
    {
        parent::__construct($name, $description, $validators);

        $this->withValidator(Validator::floatValidator());
    }

    public function withDefaultValue(float $defaultValue) : FloatOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
