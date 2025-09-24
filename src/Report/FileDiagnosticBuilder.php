<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report;

use Override;
use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\Report\FileDiagnosticBuilderInterface;
use Phpcq\Runner\Report\Buffer\FileRangeBuffer;

final class FileDiagnosticBuilder implements FileDiagnosticBuilderInterface
{
    /**
     * @var list<FileRangeBuffer>
     */
    private array $ranges = [];

    /**
     * @var callable(list<FileRangeBuffer>, FileDiagnosticBuilder): void
     */
    private $callback;

    /** @param callable(list<FileRangeBuffer>, FileDiagnosticBuilder): void $callback */
    public function __construct(
        private readonly DiagnosticBuilderInterface $parent,
        private readonly string $file,
        callable $callback
    ) {
        $this->callback = $callback;
    }

    /**
     * @return self
     */
    #[Override]
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

    #[Override]
    public function end(): DiagnosticBuilderInterface
    {
        if (empty($this->ranges)) {
            $this->ranges[] = new FileRangeBuffer($this->file, null, null, null, null);
        }
        call_user_func($this->callback, $this->ranges, $this);

        return $this->parent;
    }
}
