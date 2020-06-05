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

    /** @var string|null */
    private $mimeType;

    public function __construct(string $absolutePath, string $localName, ?string $mimeType)
    {
        if ('/' !== $absolutePath[0]) {
            throw new RuntimeException('Absolute path expected but got: "' . $absolutePath . '"');
        }

        $this->absolutePath = $absolutePath;
        $this->localName    = $localName;
        $this->mimeType     = $mimeType;
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
