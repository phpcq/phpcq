<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report;

use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\Report\FileDiagnosticBuilderInterface;
use Phpcq\Runner\Report\Buffer\FileRangeBuffer;

final class FileDiagnosticBuilder implements FileDiagnosticBuilderInterface
{
    /** @var DiagnosticBuilderInterface */
    private $parent;

    /**
     * @var FileRangeBuffer[]
     * @psalm-var array<int, FileRangeBuffer>
     */
    private $ranges = [];

    /** @var string */
    private $file;

    /**
     * @var callable
     * @psalm-var callable(array<int, FileRangeBuffer>, FileDiagnosticBuilder): void
     */
    private $callback;

    /** @psalm-param callable(array<int, FileRangeBuffer>, FileDiagnosticBuilder): void $callback */
    public function __construct(DiagnosticBuilderInterface $parent, string $file, callable $callback)
    {
        $this->parent   = $parent;
        $this->file     = $file;
        $this->callback = $callback;
    }

    /**
     * @return self
     */
    #[\Override]
    public function forRange(
        int $line,
        ?int $column = null,
        ?int $endline = null,
        ?int $endcolumn = null
    ): FileDiagnosticBuilderInterface {
        $this->ranges[] = new FileRangeBuffer(
            $this->file,
            $line,
            $column,
            $endline,
            $endcolumn
        );

        return $this;
    }

    #[\Override]
    public function end(): DiagnosticBuilderInterface
    {
        if (empty($this->ranges)) {
            $this->ranges[] = new FileRangeBuffer($this->file, null, null, null, null);
        }
        call_user_func($this->callback, $this->ranges, $this);

        return $this->parent;
    }
}
