<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\BoolOptionBuilderInterface;

/**
 * @psalm-import-type TValidator from \Phpcq\Runner\Config\Validation\Validator
 * @extends AbstractOptionBuilder<BoolOptionBuilderInterface, bool>
 */
final class BoolOptionBuilder extends AbstractOptionBuilder implements BoolOptionBuilderInterface
{
    /** @psalm-param list<TValidator> $validators */
    public function __construct(string $name, string $description, array $validators = [])
    {
        parent::__construct($name, $description, $validators);

        $this->withValidator(Validator::boolValidator());
    }

    public function isRequired(): BoolOptionBuilderInterface
    {
        return parent::isRequired();
    }

    public function withNormalizer(callable $normalizer): BoolOptionBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    public function withValidator(callable $validator): BoolOptionBuilderInterface
    {
        return parent::withValidator($validator);
    }

    public function withDefaultValue(bool $defaultValue): BoolOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
