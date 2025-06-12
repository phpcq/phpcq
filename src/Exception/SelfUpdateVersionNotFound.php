<?php

declare(strict_types=1);

namespace Phpcq\Runner\Exception;

use Throwable;

final class SelfUpdateVersionNotFound extends RuntimeException implements Exception
{
    public function __construct(string $constraint = '*', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('PHPCQ version matching constraint ' . $constraint . ' not found', $code, $previous);
    }
}
