<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Exception\ConfigurationValidationFailedException;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

use function sprintf;

/**
 * @psalm-template TType
 * @psalm-import-type TValidator from \Phpcq\Config\Validation\Validator
 */
abstract class AbstractOptionBuilder implements ConfigOptionBuilderInterface
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $description;

    /** @var bool */
    protected $required = false;

    /** @psalm-var TType|null */
    protected $defaultValue;

    /**
     * @var callable[]
     * @psalm-var list<callable(mixed): mixed>
     */
    protected $normalizer = [];

    /**
     * @var callable[]
     * @psalm-var list<callable(mixed): void>
     */
    protected $validators;

    /** @psalm-param list<TValidator> $validators */
    public function __construct(string $name, string $description, array $validators = [])
    {
        $this->name        = $name;
        $this->description = $description;
        $this->validators  = $validators;
    }

    public function isRequired(): OptionBuilderInterface
    {
        $this->required = true;

        return $this;
    }

    /** @psalm-param callable(mixed): void $normalizer */
    public function withNormalizer(callable $normalizer): OptionBuilderInterface
    {
        $this->normalizer[] = $normalizer;

        return $this;
    }

    /** @psalm-param callable(mixed): void $validator */
    public function withValidator(callable $validator): OptionBuilderInterface
    {
        $this->validators[] = $validator;

        return $this;
    }

    public function normalizeValue($value)
    {
        if (null === $value) {
            $value = $this->defaultValue;
        }

        foreach ($this->normalizer as $normalizer) {
            /** @psalm-suppress MixedAssignment */
            $value = $normalizer($value);
        }

        return $value;
    }

    public function validateValue($value): void
    {
        try {
            if (null === $value) {
                if (!$this->required) {
                    return;
                }

                throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
            }

            foreach ($this->validators as $validator) {
                $validator($value);
            }
        } catch (InvalidConfigurationException $exception) {
            throw ConfigurationValidationFailedException::fromRootError([$this->name], $exception);
        }
    }

    public function selfValidate(): void
    {
    }
}
