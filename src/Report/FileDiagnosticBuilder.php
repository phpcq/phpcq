<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\Report\FileDiagnosticBuilderInterface;
use Phpcq\Report\Buffer\FileRangeBuffer;

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

    public function forRange(
        int $startLine,
        ?int $startColumn = null,
        ?int $endLine = null,
        ?int $endColumn = null
    ): FileDiagnosticBuilderInterface {
        $this->ranges[] = new FileRangeBuffer(
            $this->file,
            $startLine,
            $startColumn,
            $endLine,
            $endColumn
        );

        return $this;
    }

    public function end(): DiagnosticBuilderInterface
    {
        if (empty($this->ranges)) {
            $this->ranges[] = new FileRangeBuffer($this->file, null, null, null, null);
        }
        call_user_func($this->callback, $this->ranges, $this);

        return $this->parent;
    }
}
