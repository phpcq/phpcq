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

    /** @var string|null */
    private $externalInfoUrl;

    /** @var null|string[] */
    private $classNames = [];

    /** @var null|string[] */
    private $categories = [];

    /**
     * @param null|FileRangeBuffer[] $fileRanges
     * @param null|string[] $classNames
     * @param null|string[] $categories
     */
    public function __construct(
        string $severity,
        string $message,
        ?string $source,
        ?array $fileRanges,
        ?string $externalInfoUrl,
        ?array $classNames,
        ?array $categories
    ) {
        $this->severity   = $severity;
        $this->message    = $message;
        $this->source     = $source;
        $this->fileRanges = $fileRanges ?: null;
        $this->externalInfoUrl = $externalInfoUrl;
        $this->classNames = $classNames ?: null;
        $this->categories = $categories ?: null;
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

    public function getExternalInfoUrl(): ?string
    {
        return $this->externalInfoUrl;
    }

    public function hasClassNames(): bool
    {
        return null !== $this->classNames;
    }

    /** @psalm-return Generator<int, string> */
    public function getClassNames(): Generator
    {
        if (null === $this->classNames) {
            return;
        }
        foreach ($this->classNames as $className) {
            yield $className;
        }
    }

    public function hasCategories(): bool
    {
        return null !== $this->categories;
    }

    /** @psalm-return Generator<int, string> */
    public function getCategories(): Generator
    {
        if (null === $this->categories) {
            return;
        }
        foreach ($this->categories as $category) {
            yield $category;
        }
    }
}
