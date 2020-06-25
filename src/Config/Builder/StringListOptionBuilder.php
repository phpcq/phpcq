<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\Exception\ConfigurationValidationErrorException;
use Phpcq\PluginApi\Version10\Configuration\Builder\StringListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Throwable;

use function sprintf;

/**
 * @extends AbstractOptionBuilder<StringListOptionBuilderInterface, list<string>>
 */
final class StringListOptionBuilder extends AbstractOptionBuilder implements StringListOptionBuilderInterface
{
    public function __construct(string $name, string $description)
    {
        parent::__construct($name, $description, [Validator::stringValidator()]);
    }

    public function isRequired(): StringListOptionBuilderInterface
    {
        return parent::isRequired();
    }

    public function withNormalizer(callable $normalizer): StringListOptionBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    public function withValidator(callable $validator): StringListOptionBuilderInterface
    {
        return parent::withValidator($validator);
    }

    /** @psalm-param list<string> $values */
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
        /** @psalm-suppress MixedAssignment */
        foreach ($values as $index => $options) {
            try {
                foreach ($this->normalizer as $normalizer) {
                    /** @psalm-suppress MixedAssignment */
                    $values[$index] = $normalizer($options);
                }
            } catch (ConfigurationValidationErrorException $exception) {
                throw $exception->withOuterPath([$this->name, (string) $index]);
            } catch (Throwable $exception) {
                throw ConfigurationValidationErrorException::fromError([$this->name, (string) $index], $exception);
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

            throw ConfigurationValidationErrorException::withCustomMessage(
                [$this->name],
                sprintf('Configuration key "%s" has to be set', $this->name)
            );
        }

        $options = Constraints::listConstraint($options, Validator::stringValidator());
        /** @psalm-var list<string> $options */
        foreach ($options as $index => $value) {
            try {
                foreach ($this->validators as $validator) {
                    $validator($value);
                }
            } catch (ConfigurationValidationErrorException $exception) {
                throw $exception->withOuterPath([$this->name, (string) $index]);
            } catch (Throwable $exception) {
                throw ConfigurationValidationErrorException::fromError([$this->name, (string) $index], $exception);
            }
        }
    }
}
