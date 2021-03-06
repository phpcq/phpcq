<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config;

use Phpcq\PluginApi\Version10\ProjectConfigInterface;

class ProjectConfiguration implements ProjectConfigInterface
{
    /**
     * @var string
     */
    private $rootPath;

    /**
     * @var string[]
     */
    private $directories;

    /**
     * @var string
     */
    private $artifactOutputPath;

    /**
     * @var int
     */
    private $maxCpuCores;

    /**
     * Create a new instance.
     *
     * @param string   $rootPath
     * @param string[] $directories
     * @param string   $artifactOutputPath
     * @param int      $maxCpuCores
     */
    public function __construct(string $rootPath, array $directories, string $artifactOutputPath, int $maxCpuCores)
    {
        $this->rootPath           = $rootPath;
        $this->directories        = $directories;
        $this->artifactOutputPath = $artifactOutputPath;
        $this->maxCpuCores        = $maxCpuCores;
    }

    public function getProjectRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * @return string[]
     *
     * @psalm-return array<array-key, string>
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    public function getArtifactOutputPath(): string
    {
        return $this->artifactOutputPath;
    }

    public function getMaxCpuCores(): int
    {
        return $this->maxCpuCores;
    }
}
