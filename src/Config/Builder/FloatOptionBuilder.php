<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;

/**
 * @psalm-import-type TValidator from \Phpcq\Runner\Config\Validation\Validator
 * @extends AbstractOptionBuilder<FloatOptionBuilderInterface, float>
 */
final class FloatOptionBuilder extends AbstractOptionBuilder implements FloatOptionBuilderInterface
{
    /** @psalm-param list<TValidator> $validators */
    public function __construct(string $name, string $description, array $validators = [])
    {
        parent::__construct($name, $description, $validators);

        $this->withValidator(Validator::floatValidator());
    }

    #[\Override]
    public function isRequired(): FloatOptionBuilderInterface
    {
        return parent::isRequired();
    }

    #[\Override]
    public function withNormalizer(callable $normalizer): FloatOptionBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    #[\Override]
    public function withValidator(callable $validator): FloatOptionBuilderInterface
    {
        return parent::withValidator($validator);
    }

    #[\Override]
    public function withDefaultValue(float $defaultValue): FloatOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
