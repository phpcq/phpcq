<?php

declare(strict_types=1);

namespace Phpcq\Report\Buffer;

final class SourceFileDiagnostic
{
    /** @var string */
    private $severity;

    /** @var string */
    private $message;

    /** @var string|null */
    private $source;

    /** @var int|null */
    private $line;

    /** @var int|null */
    private $column;

    public function __construct(
        string $severity,
        string $message,
        ?string $source,
        ?int $line,
        ?int $column
    ) {
        $this->severity = $severity;
        $this->message  = $message;
        $this->source   = $source;
        $this->line     = $line;
        $this->column   = $column;
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

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function getColumn(): ?int
    {
        return $this->column;
    }
}
