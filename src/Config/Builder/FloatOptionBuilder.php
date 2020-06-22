<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;

/**
 * @psalm-import-type TValidator from \Phpcq\Config\Validation\Validator
 */
final class FloatOptionBuilder extends AbstractOptionBuilder implements FloatOptionBuilderInterface
{
    /** @psalm-param list<TValidator> $validators */
    public function __construct(string $name, string $description, array $validators = [])
    {
        parent::__construct($name, $description, $validators);

        $this->withValidator(Validator::floatValidator());
    }

    public function withDefaultValue(float $defaultValue): FloatOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
