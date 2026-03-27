<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Buffer;

final readonly class FileRangeBuffer
{
    public function __construct(
        private string $file,
        private ?int $startLine,
        private ?int $startColumn,
        private ?int $endLine,
        private ?int $endColumn
    ) {
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getStartLine(): ?int
    {
        return $this->startLine;
    }

    public function getStartColumn(): ?int
    {
        return $this->startColumn;
    }

    public function getEndLine(): ?int
    {
        return $this->endLine;
    }

    public function getEndColumn(): ?int
    {
        return $this->endColumn;
    }
}
