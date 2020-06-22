<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\IntOptionBuilderInterface;

final class IntOptionBuilder extends AbstractOptionBuilder implements IntOptionBuilderInterface
{
    public function __construct(string $name, string $description, array $validators = [])
    {
        parent::__construct($name, $description, $validators);

        $this->withValidator(Validator::intValidator());
    }

    public function withDefaultValue(int $defaultValue) : IntOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
