<?php

declare(strict_types=1);

namespace Phpcq\Report;

use DOMElement;
use DOMNode;

use function sprintf;

final class FileError
{
    /** @var string */
    private $severity;

    /** @var string */
    private $message;

    /** @var int|null */
    private $line;

    /** @var int|null */
    private $column;

    /** @var string|null */
    private $source;

    /** @var string */
    private $tool;

    public function __construct(
        string $severity,
        string $message,
        string $tool,
        ?int $line,
        ?int $column,
        ?string $source
    ) {
        $this->severity = $severity;
        $this->message  = $message;
        $this->line     = $line;
        $this->column   = $column;
        $this->source   = $source;
        $this->tool     = $tool;
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

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function getColumn(): ?int
    {
        return $this->column;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getTool(): string
    {
        return $this->tool;
    }

    public function appendToXml(DOMNode $element): void
    {
        $node = $element->appendChild(new DOMElement('error'));
        $node->setAttribute('severity', $this->getSeverity());
        $node->setAttribute('message', $this->getMessage());

        if ($source = $this->getSource()) {
            $node->setAttribute('source', sprintf('%s: %s', $this->getTool(), $source));
        } else {
            $node->setAttribute('source', $this->getTool());
        }

        if ($line = $this->getLine()) {
            $node->setAttribute('line', (string) $line);
        }

        if ($column = $this->getColumn()) {
            $node->setAttribute('column', (string) $column);
        }
    }
}
