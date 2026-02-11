<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Config\Validation\Constraints;
use Phpcq\Runner\Config\Validation\Validator;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
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

    #[\Override]
    public function isRequired(): StringListOptionBuilderInterface
    {
        return parent::isRequired();
    }

    #[\Override]
    public function withNormalizer(callable $normalizer): StringListOptionBuilderInterface
    {
        return parent::withNormalizer($normalizer);
    }

    #[\Override]
    public function withValidator(callable $validator): StringListOptionBuilderInterface
    {
        return parent::withValidator($validator);
    }

    /**
     * @param list<string> $defaultValue
     * @return $this
     */
    #[\Override]
    public function withDefaultValue(array $defaultValue): StringListOptionBuilderInterface
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

        $raw = Constraints::listConstraint($raw);
        /** @psalm-suppress MixedAssignment */
        foreach ($raw as $index => $options) {
            try {
                foreach ($this->normalizer as $normalizer) {
                    /** @psalm-suppress MixedAssignment */
                    $raw[$index] = $normalizer($options);
                }
            } catch (ConfigurationValidationErrorException $exception) {
                throw $exception->withOuterPath([$this->name, (string) $index]);
            } catch (Throwable $exception) {
                throw ConfigurationValidationErrorException::fromError([$this->name, (string) $index], $exception);
            }
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

            throw ConfigurationValidationErrorException::withCustomMessage(
                [$this->name],
                sprintf('Configuration key "%s" has to be set', $this->name)
            );
        }

        $value = Constraints::listConstraint($value, Validator::stringValidator());
        /** @var list<string> $value */
        foreach ($value as $index => $option) {
            try {
                foreach ($this->validators as $validator) {
                    $validator($option);
                }
            } catch (ConfigurationValidationErrorException $exception) {
                throw $exception->withOuterPath([$this->name, (string) $index]);
            } catch (Throwable $exception) {
                throw ConfigurationValidationErrorException::fromError([$this->name, (string) $index], $exception);
            }
        }
    }
}
