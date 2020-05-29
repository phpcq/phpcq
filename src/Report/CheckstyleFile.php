<?php

declare(strict_types=1);

namespace Phpcq\Report;

use ArrayIterator;
use IteratorAggregate;
use Phpcq\PluginApi\Version10\CheckstyleFileInterface;
use Traversable;

/**
 * @template-implements IteratorAggregate<int, FileError>
 */
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

    /**
     * @return Traversable|FileError[]
     * @psalm-return Traversable<int, FileError>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->errors);
    }
}
