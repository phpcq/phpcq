<?php

declare(strict_types=1);

namespace Phpcq\Runner;

use Phpcq\PluginApi\Version10\EnvironmentInterface;
use Phpcq\PluginApi\Version10\PluginInterface;
use Phpcq\PluginApi\Version10\ProjectConfigInterface;
use Phpcq\PluginApi\Version10\Task\TaskFactoryInterface;

class Environment implements EnvironmentInterface
{
    /**
     * @param ProjectConfigInterface $projectConfiguration
     * @param TaskFactoryInterface   $taskFactory
     * @param string                 $tempDirectory
     */
    public function __construct(
        private readonly ProjectConfigInterface $projectConfiguration,
        private readonly TaskFactoryInterface $taskFactory,
        private readonly string $tempDirectory,
        private readonly int $availableThreads,
        private string $pluginDirectory
    ) {
    }

    #[\Override]
    public function getProjectConfiguration(): ProjectConfigInterface
    {
        return $this->projectConfiguration;
    }

    #[\Override]
    public function getTaskFactory(): TaskFactoryInterface
    {
        return $this->taskFactory;
    }

    #[\Override]
    public function getBuildTempDir(): string
    {
        return $this->tempDirectory;
    }

    #[\Override]
    public function getUniqueTempFile(?PluginInterface $plugin = null, ?string $suffix = null): string
    {
        $fileName = uniqid($plugin ? $plugin->getName() : '');
        if (!empty($suffix)) {
            $fileName .= '.' . $suffix;
        }

        return $this->getBuildTempDir() . '/' . $fileName;
    }

    #[\Override]
    public function getAvailableThreads(): int
    {
        return $this->availableThreads;
    }

    #[\Override]
    public function getInstalledDir(): string
    {
        return $this->pluginDirectory;
    }

    public function withInstalledDir(string $installedDir): self
    {
        $clone = clone $this;
        $clone->pluginDirectory = $installedDir;

        return $clone;
    }
}
