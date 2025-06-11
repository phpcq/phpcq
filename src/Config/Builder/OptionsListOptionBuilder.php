<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Config\Validation\Constraints;
use Phpcq\Runner\Config\Validation\Validator;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionsListOptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Throwable;

use function sprintf;

/** @extends AbstractOptionBuilder<OptionsListOptionBuilderInterface, list<array<string,mixed>>> */
final class OptionsListOptionBuilder extends AbstractOptionBuilder implements OptionsListOptionBuilderInterface
{
    use OptionsBuilderTrait;

    #[\Override]
    public function isRequired(): OptionsListOptionBuilderInterface
    {
        return parent::isRequired();
    }

    #[\Override]
    public function withNormalizer(callable $normalizer): OptionsListOptionBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    #[\Override]
    public function withValidator(callable $validator): OptionsListOptionBuilderInterface
    {
        return parent::withValidator($validator);
    }

    #[\Override]
    public function withDefaultValue(array $defaultValue): OptionsListOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    #[\Override]
    public function normalizeValue($raw): ?array
    {
        if (null === $raw) {
            $raw = $this->defaultValue;
        }
        if ($raw === null) {
            if ($this->required) {
                throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
            }

            return null;
        }

        /** @psalm-var list<array<string,mixed>> $raw */
        $raw = Constraints::listConstraint($raw);
        foreach ($raw as $index => $options) {
            foreach ($this->normalizer as $normalizer) {
                try {
                    /** @psalm-var array<string,mixed> */
                    $raw[$index] = $normalizer($options);
                } catch (ConfigurationValidationErrorException $exception) {
                    throw $exception->withOuterPath([$this->name, (string) $index]);
                } catch (Throwable $exception) {
                    throw ConfigurationValidationErrorException::fromError([$this->name, (string) $index], $exception);
                }
            }

            $raw[$index] = $this->normalizeOptions($raw[$index]);
        }

        return $raw;
    }

    #[\Override]
    public function validateValue($value): void
    {
        if (null === $value) {
            if (!$this->required) {
                return;
            }

            throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
        }

        /** @psalm-var list<array<string,mixed>> $value */
        $value = Constraints::listConstraint($value, Validator::arrayValidator());
        foreach ($value as $key => $option) {
            foreach ($this->validators as $validator) {
                try {
                    $validator($option);
                } catch (ConfigurationValidationErrorException $exception) {
                    throw $exception->withOuterPath([$this->name, (string) $key]);
                } catch (Throwable $exception) {
                    throw ConfigurationValidationErrorException::fromError([$this->name, (string) $key], $exception);
                }
            }
        }
    }
}
