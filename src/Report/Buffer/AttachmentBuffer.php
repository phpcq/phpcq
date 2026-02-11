<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Buffer;

use Phpcq\Runner\Exception\RuntimeException;

/**
 * Holds information for an attachment.
 */
class AttachmentBuffer
{
    public function __construct(
        private readonly string $absolutePath,
        private readonly string $localName,
        private readonly ?string $mimeType
    ) {
        if ('/' !== $absolutePath[0]) {
            throw new RuntimeException('Absolute path expected but got: "' . $absolutePath . '"');
        }
    }

    public function getAbsolutePath(): string
    {
        return $this->absolutePath;
    }

    public function getLocalName(): string
    {
        return $this->localName;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }
}
