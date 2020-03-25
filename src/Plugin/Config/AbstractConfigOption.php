<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use Phpcq\Exception\InvalidConfigException;
use function gettype;
use function sprintf;

/**
 * @psalm-template V
 */
abstract class AbstractConfigOption implements ConfigOptionInterface
{
    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /**
     * @var mixed
     *
     * @psalm-var V
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $required;

    /**
     * @psalm-param V $defaultValue
     *
     * @param string $name
     * @param string $description
     * @param mixed  $defaultValue
     * @param bool   $required
     */
    public function __construct(string $name, string $description, $defaultValue, bool $required)
    {
        $this->name         = $name;
        $this->description  = $description;
        $this->defaultValue = $defaultValue;
        $this->required     = $required;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get defaultValue.
     *
     * @return mixed
     *
     * @psalm-return V
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function isRequired() : bool
    {
        return $this->required;
    }

    /**
     * Throws an exception for an invalid value.
     *
     * @param mixed $value Invalid value.
     *
     * @throws InvalidConfigException Always the method is called.
     */
    protected function throwException($value) : void
    {
        throw new InvalidConfigException(
            sprintf(
                'Config option "%s" has to be of type "%s", "%s" given.',
                $this->getName(),
                $this->getType(),
                gettype($value)
            )
        );
    }
}
