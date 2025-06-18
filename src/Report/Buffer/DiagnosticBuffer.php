<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Buffer;

use Generator;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;

/**
 * @psalm-type TDiagnosticSeverity = TaskReportInterface::SEVERITY_NONE|TaskReportInterface::SEVERITY_INFO|TaskReportInterface::SEVERITY_MARGINAL|TaskReportInterface::SEVERITY_MINOR|TaskReportInterface::SEVERITY_MAJOR|TaskReportInterface::SEVERITY_FATAL
 */
final class DiagnosticBuffer
{
    /** @var null|FileRangeBuffer[] */
    private $fileRanges;

    /** @var null|string[] */
    private $classNames = [];

    /** @var null|string[] */
    private $categories = [];

    /**
     * @param null|FileRangeBuffer[] $fileRanges
     * @param null|string[] $classNames
     * @param null|string[] $categories
     *
     * @psalm-param TDiagnosticSeverity $severity
     */
    public function __construct(
        private readonly string $severity,
        private readonly string $message,
        private readonly ?string $source,
        ?array $fileRanges,
        private readonly ?string $externalInfoUrl,
        ?array $classNames,
        ?array $categories
    ) {
        $this->fileRanges = $fileRanges ?: null;
        $this->classNames = $classNames ?: null;
        $this->categories = $categories ?: null;
    }

    /**
     * Get severity.
     *
     * @return string
     * @psalm-return TDiagnosticSeverity
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

    public function hasFileRanges(): bool
    {
        return null !== $this->fileRanges;
    }

    /** @psalm-return Generator<int, FileRangeBuffer> */
    public function getFileRanges(): Generator
    {
        if (null === $this->fileRanges) {
            return;
        }
        foreach ($this->fileRanges as $range) {
            yield $range;
        }
    }

    public function getExternalInfoUrl(): ?string
    {
        return $this->externalInfoUrl;
    }

    public function hasClassNames(): bool
    {
        return null !== $this->classNames;
    }

    /** @psalm-return Generator<int, string> */
    public function getClassNames(): Generator
    {
        if (null === $this->classNames) {
            return;
        }
        foreach ($this->classNames as $className) {
            yield $className;
        }
    }

    public function hasCategories(): bool
    {
        return null !== $this->categories;
    }

    /** @psalm-return Generator<int, string> */
    public function getCategories(): Generator
    {
        if (null === $this->categories) {
            return;
        }
        foreach ($this->categories as $category) {
            yield $category;
        }
    }
}
