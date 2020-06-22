<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringOptionBuilderInterface;

/**
 * @psalm-import-type TValidator from \Phpcq\Config\Validation\Validator
 */
final class StringOptionBuilder extends AbstractOptionBuilder implements StringOptionBuilderInterface
{
    /** @psalm-param list<TValidator> $validators */
    public function __construct(string $name, string $description, array $validators = [])
    {
        parent::__construct($name, $description, $validators);

        $this->withValidator(Validator::stringValidator());
    }

    public function withDefaultValue(string $defaultValue): StringOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
