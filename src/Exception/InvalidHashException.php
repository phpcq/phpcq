<?php

declare(strict_types=1);

namespace Phpcq\Runner\Exception;

use RuntimeException;

class InvalidHashException extends RuntimeException implements Exception
{
    /**
     * Create a new instance.
     *
     * @param string $hashType
     * @param string $hashValue
     */
    public function __construct(private readonly string $hashType, private readonly string $hashValue)
    {
        parent::__construct('Invalid hash type: ' . $this->hashType . ' (' . $this->hashValue . ')');
    }

    public function getHashType(): string
    {
        return $this->hashType;
    }

    public function getHashValue(): string
    {
        return $this->hashValue;
    }
}
