<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\IntOptionBuilderInterface;

/**
 * @psalm-import-type TValidator from \Phpcq\Config\Validation\Validator
 * @extends AbstractOptionBuilder<IntOptionBuilderInterface, int>
 */
final class IntOptionBuilder extends AbstractOptionBuilder implements IntOptionBuilderInterface
{
    /** @psalm-param list<TValidator> $validators */
    public function __construct(string $name, string $description, array $validators = [])
    {
        parent::__construct($name, $description, $validators);

        $this->withValidator(Validator::intValidator());
    }

    public function isRequired(): IntOptionBuilderInterface
    {
        return parent::isRequired();
    }

    public function withNormalizer(callable $normalizer): IntOptionBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    public function withValidator(callable $validator): IntOptionBuilderInterface
    {
        return parent::withValidator($validator);
    }

    public function withDefaultValue(int $defaultValue): IntOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
