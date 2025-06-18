<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config;

use Phpcq\PluginApi\Version10\ProjectConfigInterface;

class ProjectConfiguration implements ProjectConfigInterface
{
    /**
     * Create a new instance.
     *
     * @param string   $rootPath
     * @param string[] $directories
     * @param string   $artifactOutputPath
     * @param int      $maxCpuCores
     */
    public function __construct(
        private readonly string $rootPath,
        private readonly array $directories,
        private readonly string $artifactOutputPath,
        private readonly int $maxCpuCores
    ) {
    }

    #[\Override]
    public function getProjectRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * @return string[]
     *
     * @psalm-return array<array-key, string>
     */
    #[\Override]
    public function getDirectories(): array
    {
        return $this->directories;
    }

    #[\Override]
    public function getArtifactOutputPath(): string
    {
        return $this->artifactOutputPath;
    }

    #[\Override]
    public function getMaxCpuCores(): int
    {
        return $this->maxCpuCores;
    }
}
