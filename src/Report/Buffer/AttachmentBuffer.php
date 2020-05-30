<?php

declare(strict_types=1);

namespace Phpcq\Report\Buffer;

use Phpcq\Exception\RuntimeException;

/**
 * Holds information for an attachment.
 */
class AttachmentBuffer
{
    /** @var string */
    private $absolutePath;

    /** @var string */
    private $localName;

    public function __construct(string $absolutePath, ?string $localName)
    {
        if ('/' !== $absolutePath[0]) {
            throw new RuntimeException('Absolute path expected but got: "' . $absolutePath . '"');
        }

        $this->absolutePath = $absolutePath;
        $this->localName    = $localName ?: basename($absolutePath);
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
