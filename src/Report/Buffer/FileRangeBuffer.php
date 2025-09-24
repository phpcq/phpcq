<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Buffer;

final class FileRangeBuffer
{
    public function __construct(
        private readonly string $file,
        private readonly ?int $startLine,
        private readonly ?int $startColumn,
        private readonly ?int $endLine,
        private readonly ?int $endColumn
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
