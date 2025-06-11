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
     * @var ProjectConfigInterface
     */
    private $projectConfiguration;

    /**
     * @var TaskFactoryInterface
     */
    private $taskFactory;

    /**
     * @var string
     */
    private $tempDirectory;

    /** @var int */
    private $availableThreads;

    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @param ProjectConfigInterface $projectConfiguration
     * @param TaskFactoryInterface   $taskFactory
     * @param string                 $tempDirectory
     */
    public function __construct(
        ProjectConfigInterface $projectConfiguration,
        TaskFactoryInterface $taskFactory,
        string $tempDirectory,
        int $availableThreads,
        string $pluginDirectory
    ) {
        $this->projectConfiguration = $projectConfiguration;
        $this->taskFactory          = $taskFactory;
        $this->tempDirectory        = $tempDirectory;
        $this->availableThreads     = $availableThreads;
        $this->pluginDirectory      = $pluginDirectory;
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
