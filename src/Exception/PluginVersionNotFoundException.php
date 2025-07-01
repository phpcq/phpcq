<?php

declare(strict_types=1);

namespace Phpcq\Runner\Exception;

use Throwable;

class PluginVersionNotFoundException extends \RuntimeException implements Exception
{
    /**
     * Create a new instance.
     *
     * @param string         $pluginName
     * @param string         $constraint
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(
        private readonly string $pluginName,
        private readonly string $constraint,
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            'Plugin not found: ' . $pluginName . ':' . $constraint,
            $code,
            $previous
        );
    }

    /**
     * Retrieve pluginName.
     *
     * @return string
     */
    public function getPluginName(): string
    {
        return $this->pluginName;
    }

    /**
     * Retrieve versionConstraint.
     *
     * @return string
     */
    public function getVersionConstraint(): string
    {
        return $this->constraint;
    }
}
