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
 * @template-implements IteratorAggregate<int, SourceFileDiagnostic>
 */
final class SourceFileBuffer implements IteratorAggregate, Countable
{
    /** @var SourceFileDiagnostic[] */
    private $diagnostics = [];

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

    public function addDiagnostic(string $severity, string $message, ?string $source, ?int $line, ?int $column): void
    {
        $this->diagnostics[] = new SourceFileDiagnostic($severity, $message, $source, $line, $column);
    }

    public function count(): int
    {
        return count($this->diagnostics);
    }

    /**
     * @return Traversable|SourceFileDiagnostic[]
     * @psalm-return Traversable<int, SourceFileDiagnostic>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->diagnostics);
    }
}
