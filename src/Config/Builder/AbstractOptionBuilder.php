<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Throwable;

use function sprintf;

/**
 * @psalm-template TReturnType
 * @psalm-template TType
 * @psalm-import-type TValidator from \Phpcq\Runner\Config\Validation\Validator
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

    /**
     * @return $this
     *
     * @psalm-return TReturnType
     * @psalm-suppress InvalidReturnStatement - Works for child classes
     * @psalm-suppress InvalidReturnType - Works for child classes
     */
    public function isRequired()
    {
        $this->required = true;

        return $this;
    }

    /**
     * @psalm-param callable(mixed): void $normalizer
     *
     * @psalm-return TReturnType
     * @psalm-suppress InvalidReturnStatement - Works for child classes
     * @psalm-suppress InvalidReturnType - Works for child classes
     */
    public function withNormalizer(callable $normalizer)
    {
        $this->normalizer[] = $normalizer;

        return $this;
    }

    /**
     * @psalm-param callable(mixed): void $validator
     *
     * @return $this
     * @psalm-return TReturnType
     * @psalm-suppress InvalidReturnStatement - Works for child classes
     * @psalm-suppress InvalidReturnType - Works for child classes
     */
    public function withValidator(callable $validator)
    {
        $this->validators[] = $validator;

        return $this;
    }

    public function normalizeValue($raw)
    {
        if (null === $raw) {
            $raw = $this->defaultValue;
        }

        try {
            foreach ($this->normalizer as $normalizer) {
                /** @psalm-suppress MixedAssignment */
                $raw = $normalizer($raw);
            }
        } catch (ConfigurationValidationErrorException $exception) {
            throw $exception->withOuterPath([$this->name]);
        } catch (Throwable $exception) {
            throw ConfigurationValidationErrorException::fromError([$this->name], $exception);
        }

        return $raw;
    }

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

        try {
            foreach ($this->validators as $validator) {
                $validator($value);
            }
        } catch (ConfigurationValidationErrorException $exception) {
            throw $exception->withOuterPath([$this->name]);
        } catch (Throwable $exception) {
            throw ConfigurationValidationErrorException::fromError([$this->name], $exception);
        }
    }

    public function selfValidate(): void
    {
    }
}
