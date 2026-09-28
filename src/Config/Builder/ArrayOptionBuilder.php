<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Config\Validation\Validator;

/**
 * Describes an array option whose content is not described by the builder.
 *
 * It is used for configuration values validated elsewhere, e.g. the plugin configuration of a task which can only be
 * validated as soon as the plugin is loaded.
 *
 * @psalm-import-type TValidator from \Phpcq\Runner\Config\Validation\Validator
 * @extends AbstractOptionBuilder<ArrayOptionBuilder, array>
 */
final class ArrayOptionBuilder extends AbstractOptionBuilder
{
    /** @param list<TValidator> $validators */
    public function __construct(string $name, string $description, array $validators = [])
    {
        parent::__construct($name, $description, $validators);

        $this->withValidator(Validator::arrayValidator());
    }

    public function withDefaultValue(array $defaultValue): self
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
