<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use function sprintf;

final class StringListOptionBuilder extends AbstractOptionBuilder implements StringListOptionBuilderInterface
{
    public function __construct(string $name, string $description)
    {
        parent::__construct($name, $description, [Validator::stringValidator()]);
    }

    /** @param string[] */
    public function withDefaultValue(array $values): StringListOptionBuilderInterface
    {
        $this->defaultValue = $values;

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

        $options = Constraints::listConstraint($options, Validator::stringValidator());
        foreach ($options as $value) {
            foreach ($this->validators as $validator) {
                $validator($value);
            }
        }
    }
}
