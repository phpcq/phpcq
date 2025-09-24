<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report;

use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\Report\FileDiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Report\Buffer\DiagnosticBuffer;
use Phpcq\Runner\Report\Buffer\FileRangeBuffer;

/**
 * @psalm-type TDiagnosticSeverity = TaskReportInterface::SEVERITY_NONE|TaskReportInterface::SEVERITY_INFO|TaskReportInterface::SEVERITY_MARGINAL|TaskReportInterface::SEVERITY_MINOR|TaskReportInterface::SEVERITY_MAJOR|TaskReportInterface::SEVERITY_FATAL
 */
final class DiagnosticBuilder implements DiagnosticBuilderInterface
{
    /** @var list<FileRangeBuffer> */
    private array $files = [];

    private ?string $source = null;

    /**
     * @var callable
     * @var callable(DiagnosticBuffer, DiagnosticBuilder): void
     */
    private $callback;

    /** @var array<string, FileDiagnosticBuilder> */
    private array $pendingFiles = [];

    private ?string $externalInfoUrl = null;

    /** @var array<string, string> */
    private array $classNames = [];

    /** @var array<string, string> */
    private array $categories = [];

    /**
     * @param callable(DiagnosticBuffer, DiagnosticBuilder): void $callback
     * @param TDiagnosticSeverity $severity
     */
    public function __construct(
        private readonly TaskReportInterface $parent,
        private readonly string $severity,
        private readonly string $message,
        callable $callback
    ) {
        $this->callback = $callback;
    }

    #[\Override]
    public function forFile(string $file): FileDiagnosticBuilderInterface
    {
        // FIXME: if we would know the root dir, we could strip it here.
        $builder = new FileDiagnosticBuilder(
            $this,
            $file,
            /** @param list<FileRangeBuffer> $rangeBuffers */
            function (array $rangeBuffers, FileDiagnosticBuilder $builder) {
                foreach ($rangeBuffers as $rangeBuffer) {
                    $this->files[] = $rangeBuffer;
                }
                unset($this->pendingFiles[spl_object_hash($builder)]);
            }
        );

        return $this->pendingFiles[spl_object_hash($builder)] = $builder;
    }

    #[\Override]
    public function fromSource(string $source): DiagnosticBuilderInterface
    {
        $this->source = $source;

        return $this;
    }

    #[\Override]
    public function withExternalInfoUrl(string $url): DiagnosticBuilderInterface
    {
        $this->externalInfoUrl = $url;

        return $this;
    }

    #[\Override]
    public function forClass(string $className): DiagnosticBuilderInterface
    {
        $this->classNames[$className] = $className;

        return $this;
    }

    #[\Override]
    public function withCategory(string $category): DiagnosticBuilderInterface
    {
        $this->categories[$category] = $category;

        return $this;
    }

    #[\Override]
    public function end(): TaskReportInterface
    {
        foreach ($this->pendingFiles as $pendingBuilder) {
            $pendingBuilder->end();
        }
        call_user_func(
            $this->callback,
            new DiagnosticBuffer(
                $this->severity,
                $this->message,
                $this->source,
                $this->files,
                $this->externalInfoUrl,
                array_values($this->classNames),
                array_values($this->categories)
            ),
            $this
        );

        return $this->parent;
    }
}
