<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringOptionBuilderInterface;

/**
 * @psalm-import-type TValidator from \Phpcq\Runner\Config\Validation\Validator
 * @extends AbstractOptionBuilder<StringOptionBuilderInterface, string>
 */
final class StringOptionBuilder extends AbstractOptionBuilder implements StringOptionBuilderInterface
{
    /** @psalm-param list<TValidator> $validators */
    public function __construct(string $name, string $description, array $validators = [])
    {
        parent::__construct($name, $description, $validators);

        $this->withValidator(Validator::stringValidator());
    }

    public function isRequired(): StringOptionBuilderInterface
    {
        return parent::isRequired();
    }

    public function withNormalizer(callable $normalizer): StringOptionBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    public function withValidator(callable $validator): StringOptionBuilderInterface
    {
        return parent::withValidator($validator);
    }

    public function withDefaultValue(string $defaultValue): StringOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
