<?php

declare(strict_types=1);

namespace Phpcq\Runner\Exception;

use Throwable;

class ToolVersionNotFoundException extends \RuntimeException implements Exception
{
    /**
     * Create a new instance.
     *
     * @param string         $toolName
     * @param string         $constraint
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(
        private readonly string $toolName,
        private readonly string $constraint = '*',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            'Tool not found: ' . $toolName . ':' . $constraint,
            $code,
            $previous
        );
    }

    /**
     * Retrieve toolName.
     *
     * @return string
     */
    public function getToolName(): string
    {
        return $this->toolName;
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
