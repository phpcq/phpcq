<?php

declare(strict_types=1);

namespace Phpcq\Runner\Exception;

use Throwable;

class ToolVersionNotFoundException extends \RuntimeException implements Exception
{
    /**
     * @var string
     */
    private $toolName;

    /**
     * @var string
     */
    private $constraint;

    /**
     * Create a new instance.
     *
     * @param string         $toolName
     * @param string         $constraint
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $toolName, string $constraint = '*', int $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            'Tool not found: ' . $toolName . ':' . $constraint,
            $code,
            $previous
        );
        $this->toolName   = $toolName;
        $this->constraint = $constraint;
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
