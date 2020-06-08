<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\Report\FileDiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Buffer\DiagnosticBuffer;
use Phpcq\Report\Buffer\FileRangeBuffer;

final class DiagnosticBuilder implements DiagnosticBuilderInterface
{
    /** @var string */
    private $severity;

    /** @var string */
    private $message;

    /** @var FileRangeBuffer[] */
    private $files = [];

    /** @var string|null */
    private $source;

    /** @var ToolReportInterface */
    private $parent;

    /**
     * @var callable
     * @psalm-var callable(DiagnosticBuffer, DiagnosticBuilder): void
     */
    private $callback;

    /** @var FileDiagnosticBuilder[] */
    private $pendingFiles = [];

    /** @var string|null */
    private $externalInfoUrl;

    /** @psalm-param callable(DiagnosticBuffer, DiagnosticBuilder): void $callback */
    public function __construct(ToolReportInterface $parent, string $severity, string $message, callable $callback)
    {
        $this->severity = $severity;
        $this->message  = $message;
        $this->parent   = $parent;
        $this->callback = $callback;
    }

    public function forFile(string $file): FileDiagnosticBuilderInterface
    {
        // FIXME: if we would know the root dir, we could strip it here.
        $builder = new FileDiagnosticBuilder(
            $this,
            $file,
            /** @param FileRangeBuffer[] $rangeBuffers */
            function (array $rangeBuffers, FileDiagnosticBuilder $builder) {
                foreach ($rangeBuffers as $rangeBuffer) {
                    $this->files[] = $rangeBuffer;
                }
                unset($this->pendingFiles[spl_object_hash($builder)]);
            }
        );

        return $this->pendingFiles[spl_object_hash($builder)] = $builder;
    }

    /**
     * @return self
     */
    public function fromSource(string $source): DiagnosticBuilderInterface
    {
        $this->source = $source;

        return $this;
    }

    public function withExternalInfoUrl(string $url): DiagnosticBuilderInterface
    {
        $this->externalInfoUrl = $url;

        return $this;
    }

    public function end(): ToolReportInterface
    {
        foreach ($this->pendingFiles as $pendingBuilder) {
            $pendingBuilder->end();
        }
        call_user_func(
            $this->callback,
            new DiagnosticBuffer($this->severity, $this->message, $this->source, $this->files, $this->externalInfoUrl),
            $this
        );

        return $this->parent;
    }
}
