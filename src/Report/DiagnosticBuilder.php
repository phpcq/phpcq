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
    /**
     * @var string
     * @psalm-var TDiagnosticSeverity
     */
    private $severity;

    /** @var string */
    private $message;

    /** @var FileRangeBuffer[] */
    private $files = [];

    /** @var string|null */
    private $source;

    /** @var TaskReportInterface */
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

    /** @var string[] */
    private $classNames = [];

    /** @var string[] */
    private $categories = [];

    /**
     * @psalm-param callable(DiagnosticBuffer, DiagnosticBuilder): void $callback
     * @psalm-param TDiagnosticSeverity $severity
     */
    public function __construct(TaskReportInterface $parent, string $severity, string $message, callable $callback)
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

    public function forClass(string $className): DiagnosticBuilderInterface
    {
        $this->classNames[$className] = $className;

        return $this;
    }

    public function withCategory(string $category): DiagnosticBuilderInterface
    {
        $this->categories[$category] = $category;

        return $this;
    }

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
