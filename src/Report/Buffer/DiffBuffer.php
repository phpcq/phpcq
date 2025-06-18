<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Buffer;

use Phpcq\Runner\Exception\RuntimeException;

/**
 * Holds information for a diff.
 */
class DiffBuffer
{
    /** @var string */
    private $absolutePath;

    public function __construct(string $absolutePath, private readonly string $localName)
    {
        if ('/' !== $absolutePath[0]) {
            throw new RuntimeException('Absolute path expected but got: "' . $absolutePath . '"');
        }

        $this->absolutePath = $absolutePath;
    }

    public function getAbsolutePath(): string
    {
        return $this->absolutePath;
    }

    public function getLocalName(): string
    {
        return $this->localName;
    }
}
