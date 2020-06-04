<?php

declare(strict_types=1);

namespace Phpcq\Report\Buffer;

use Generator;

final class DiagnosticBuffer
{
    /** @var string */
    private $severity;

    /** @var string */
    private $message;

    /** @var string|null */
    private $source;

    /** @var null|FileRangeBuffer[] */
    private $fileRanges;

    /**
     * @param null|FileRangeBuffer[] $fileRanges
     */
    public function __construct(string $severity, string $message, ?string $source, ?array $fileRanges)
    {
        $this->severity   = $severity;
        $this->message    = $message;
        $this->source     = $source;
        $this->fileRanges = $fileRanges ?: null;
    }

    /**
     * Get severity.
     *
     * @return string
     */
    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function hasFileRanges(): bool
    {
        return null !== $this->fileRanges;
    }

    /** @psalm-return Generator<int, FileRangeBuffer> */
    public function getFileRanges(): Generator
    {
        if (null === $this->fileRanges) {
            return;
        }
        foreach ($this->fileRanges as $range) {
            yield $range;
        }
    }
}
