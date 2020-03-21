<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use Phpcq\Exception\InvalidConfigException;

interface ConfigOptionInterface
{
    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get default value.
     *
     * @return mixed
     */
    public function getDefaultValue();

    /**
     * Validate a given value.
     *
     * @param mixed $value Given value.
     *
     * @throws InvalidConfigException When an invalid value is detected.
     */
    public function validateValue($value) : void;
}
