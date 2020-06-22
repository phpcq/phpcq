<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use function sprintf;

final class OptionsListOptionBuilder extends AbstractOptionBuilder implements OptionsListOptionBuilderInterface
{
    use OptionsBuilderTrait;

    public function withDefaultValue(array $defaultValue): OptionsListOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function normalizeValue($values): ?array
    {
        if (null === $values) {
            $values = $this->defaultValue;
        }
        if ($values === null) {
            if ($this->required) {
                throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
            }

            return null;
        }

        $values = Constraints::listConstraint($values);
        foreach ($values as $index => $options) {
            foreach ($this->normalizer as $normalizer) {
                $values[$index] = $normalizer($options);
            }

            $values[$index] = $this->normalizeOptions($values[$index]);
        }

        return $values;
    }

    public function validateValue($options): void
    {
        if (null === $options) {
            if (!$this->required) {
                return;
            }

            throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
        }

        $options = Constraints::listConstraint($options, Validator::arrayValidator());
        foreach ($options as $value) {
            foreach ($this->validators as $validator) {
                $validator($value);
            }
        }
    }
}
