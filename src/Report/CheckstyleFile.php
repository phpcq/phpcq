<?php

declare(strict_types=1);

namespace Phpcq\Report;

use ArrayIterator;
use DOMElement;
use DOMNode;
use IteratorAggregate;
use Phpcq\PluginApi\Version10\CheckstyleFileInterface;
use Traversable;

final class CheckstyleFile implements IteratorAggregate, CheckstyleFileInterface
{
    /** @var string */
    private $fileName;

    /** @var FileError[] */
    private $errors = [];

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    public function getName(): string
    {
        return $this->fileName;
    }

    public function add(
        string $severity,
        string $message,
        string $toolName,
        ?string $source = null,
        ?int $line = null,
        ?int $column = null
    ): void {
        $this->errors[] = new FileError($severity, $message, $toolName, $line, $column, $source);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->errors);
    }

    public function appendToXml(DOMNode $node): void
    {
        $fileElement = $node->appendChild(new DOMElement('file'));
        $fileElement->setAttribute('name', $this->getName());

        foreach ($this->errors as $error) {
            $error->appendToXml($fileElement);
        }
    }
}
