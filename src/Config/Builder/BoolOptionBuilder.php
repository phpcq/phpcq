<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\BoolOptionBuilderInterface;

/**
 * @psalm-import-type TValidator from \Phpcq\Config\Validation\Validator
 */
final class BoolOptionBuilder extends AbstractOptionBuilder implements BoolOptionBuilderInterface
{
    /** @psalm-param list<TValidator> $validators */
    public function __construct(string $name, string $description, array $validators = [])
    {
        parent::__construct($name, $description, $validators);

        $this->withValidator(Validator::boolValidator());
    }

    public function withDefaultValue(bool $defaultValue): BoolOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
