<?php

declare(strict_types=1);

namespace Phpcq\Report\Buffer;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Buffers report data for a source file.
 *
 * @template-implements IteratorAggregate<int, SourceFileError>
 */
final class SourceFileBuffer implements IteratorAggregate, Countable
{
    /** @var SourceFileError[] */
    private $errors = [];

    /**
     * @var string
     */
    private $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function addError(string $severity, string $message, ?string $source, ?int $line, ?int $column): void
    {
        $this->errors[] = new SourceFileError($severity, $message, $source, $line, $column);
    }

    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * @return Traversable|SourceFileError[]
     * @psalm-return Traversable<int, SourceFileError>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->errors);
    }
}
