<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

abstract class AbstractOptionsBuilder extends AbstractOptionBuilder implements OptionsBuilderInterface
{
    use OptionsBuilderTrait;

    public function __construct(string $name, string $description)
    {
        parent::__construct($name, $description, [Validator::arrayValidator()]);
    }

    public function withDefaultValue(array $defaultValue): OptionsBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function normalizeValue($raw): ?array
    {
        $value = parent::normalizeValue($raw);
        if ($value === null) {
            if ($this->required) {
                throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
            }

            return null;
        }

        $value = Constraints::arrayConstraint($value);

        return $this->normalizeOptions($value);
    }

    public function validateValue($options): void
    {
        parent::validateValue($options);

        $diff = array_diff_key($options, $this->options);
        if (count($diff) > 0) {
            throw new InvalidConfigurationException(sprintf('Unexpected array keys "%s"', implode(', ', array_keys($diff))));
        }

        assert(is_array($options));
        foreach ($this->options as $key => $builder) {
            $builder->validateValue($options[$key] ?? null);
        }
    }
}
