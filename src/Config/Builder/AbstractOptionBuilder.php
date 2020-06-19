<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\PluginApi\Version10\Configuration\Builder\NodeBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

abstract class AbstractOptionBuilder implements ProcessConfigOptionBuilderInterface
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

    /**
     * @var NodeBuilderInterface
     */
    private $parent;

    public function __construct(
        NodeBuilderInterface $parent,
        string $name,
        string $description,
        array $validators = []
    ) {
        $this->parent      = $parent;
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


    public function withDefaultValue($defaultValue) : OptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function end() : NodeBuilderInterface
    {
        return $this->parent;
    }

    public function processConfig($raw)
    {
        $value = $this->getNormalizedValue($raw);
        if ($value === null) {
            if ($this->required) {
                throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
            }

            return null;
        }

        $this->validateValue($value);

        return $value;
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

    protected function validateValue($value): void
    {
        foreach ($this->validators as $validator) {
            $validator($value);
        }
    }
}