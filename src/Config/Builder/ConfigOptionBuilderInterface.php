<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

/**
 * Interface is a more specific OptionBuilderInterface describing internal methods which are not communicated to the
 * plugins.
 *
 * When processing a configuration value following steps are processed:
 *
 *  1. self::selfValidate()
 *  2. $value = self::normalizeValue($raw)
 *  3. self::validateValue($value)
 */
interface ConfigOptionBuilderInterface extends OptionBuilderInterface
{
    /**
     * Validates it self.
     *
     * @throws RuntimeException When option builder is in an misconfigured state.
     */
    public function selfValidate(): void;

    /**
     * Normalize given value and return it.
     *
     * @param mixed $raw Given value. Null if nothing configured.
     *
     * @return mixed
     */
    public function normalizeValue($raw);

    /**
     * Validate given value.
     *
     * @param mixed $value
     *
     * @throws InvalidConfigurationException When validation failed.
     */
    public function validateValue($value): void;
}
