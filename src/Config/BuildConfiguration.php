<?php

declare(strict_types=1);

namespace Phpcq\Config;

use Phpcq\PluginApi\Version10\BuildConfigInterface;
use Phpcq\PluginApi\Version10\ProjectConfigInterface;
use Phpcq\PluginApi\Version10\TaskFactoryInterface;

class BuildConfiguration implements BuildConfigInterface
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

    /**
     * @param ProjectConfigInterface $projectConfiguration
     * @param TaskFactoryInterface   $taskFactory
     * @param string                 $tempDirectory
     */
    public function __construct(
        ProjectConfigInterface $projectConfiguration,
        TaskFactoryInterface $taskFactory,
        string $tempDirectory
    ) {
        $this->projectConfiguration = $projectConfiguration;
        $this->taskFactory          = $taskFactory;
        $this->tempDirectory        = $tempDirectory;
    }

    public function getProjectConfiguration(): ProjectConfigInterface
    {
        return $this->projectConfiguration;
    }

    public function getTaskFactory(): TaskFactoryInterface
    {
        return $this->taskFactory;
    }

    public function getBuildTempDir(): string
    {
        return $this->tempDirectory;
    }
}