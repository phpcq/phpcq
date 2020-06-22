<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use function sprintf;

abstract class AbstractOptionBuilder implements ConfigOptionBuilderInterface
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $description;

    /** @var bool */
    protected $required = false;

    /** @psalm-var TType */
    protected $defaultValue;

    /**
     * @var callable[]
     * @psalm-var list<callable(mixed): TType>
     */
    protected $normalizer = [];

    /**
     * @var callable[]
     * @psalm-var list<callable(TType): void>
     */
    protected $validators;

    public function __construct(string $name, string $description, array $validators = [])
    {
        $this->name        = $name;
        $this->description = $description;
        $this->validators  = $validators;
    }

    public function isRequired() : OptionBuilderInterface
    {
        $this->required = true;

        return $this;
    }

    public function withNormalizer(callable $normalizer) : OptionBuilderInterface
    {
        $this->normalizer[] = $normalizer;

        return $this;
    }

    public function withValidator(callable $validator) : OptionBuilderInterface
    {
        $this->validators[] = $validator;

        return $this;
    }

    public function normalizeValue($raw)
    {
        return $this->getNormalizedValue($raw);
    }

    protected function getNormalizedValue($value)
    {
        if (null === $value) {
            $value = $this->defaultValue;
        }

        foreach ($this->normalizer as $normalizer) {
            $value = $normalizer($value);
        }

        return $value;
    }

    public function validateValue($value): void
    {
        if (null === $value) {
            if (!$this->required) {
                return;
            }

            throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
        }

        foreach ($this->validators as $validator) {
            $validator($value);
        }
    }

    public function selfValidate() : void
    {
    }
}
