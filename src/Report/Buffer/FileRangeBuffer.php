<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Buffer;

final class FileRangeBuffer
{
    /** @var string */
    private $file;
    /** @var null|int */
    private $startLine;
    /** @var null|int */
    private $startColumn;
    /** @var null|int */
    private $endLine;
    /** @var null|int */
    private $endColumn;

    public function __construct(string $file, ?int $startLine, ?int $startColumn, ?int $endLine, ?int $endColumn)
    {
        $this->file        = $file;
        $this->startLine   = $startLine;
        $this->startColumn = $startColumn;
        $this->endColumn   = $endColumn;
        $this->endLine     = $endLine;
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
