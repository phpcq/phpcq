<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Config\Validation\Constraints;
use Phpcq\Config\Validation\Validator;
use Phpcq\Exception\ConfigurationValidationErrorException;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Throwable;

use function sprintf;

/** @extends AbstractOptionBuilder<OptionsListOptionBuilderInterface, list<array<string,mixed>>> */
final class OptionsListOptionBuilder extends AbstractOptionBuilder implements OptionsListOptionBuilderInterface
{
    use OptionsBuilderTrait;

    public function isRequired(): OptionsListOptionBuilderInterface
    {
        return parent::isRequired();
    }

    public function withNormalizer(callable $normalizer): OptionsListOptionBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    public function withValidator(callable $validator): OptionsListOptionBuilderInterface
    {
        return parent::withValidator($validator);
    }

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

        /** @psalm-var list<array<string,mixed>> $values */
        $values = Constraints::listConstraint($values);
        foreach ($values as $index => $options) {
            foreach ($this->normalizer as $normalizer) {
                try {
                    /** @psalm-var array<string,mixed> */
                    $values[$index] = $normalizer($options);
                } catch (ConfigurationValidationErrorException $exception) {
                    throw $exception->withOuterPath([$this->name, (string) $index]);
                } catch (Throwable $exception) {
                    throw ConfigurationValidationErrorException::fromError([$this->name, (string) $index], $exception);
                }
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

        /** @psalm-var list<array<string,mixed>> $options */
        $options = Constraints::listConstraint($options, Validator::arrayValidator());
        foreach ($options as $key => $value) {
            foreach ($this->validators as $validator) {
                try {
                    $validator($value);
                } catch (ConfigurationValidationErrorException $exception) {
                    throw $exception->withOuterPath([$this->name, (string) $key]);
                } catch (Throwable $exception) {
                    throw ConfigurationValidationErrorException::fromError([$this->name, (string) $key], $exception);
                }
            }
        }
    }
}
