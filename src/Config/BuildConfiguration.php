<?php

declare(strict_types=1);

namespace Phpcq\Config;

use Phpcq\Task\TaskFactory;

class BuildConfiguration implements BuildConfigInterface
{
    /**
     * @var ProjectConfigInterface
     */
    private $projectConfiguration;

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * @var string
     */
    private $tempDirectory;

    /**
     * @param ProjectConfigInterface $projectConfiguration
     * @param TaskFactory            $taskFactory
     * @param string                 $tempDirectory
     */
    public function __construct(
        ProjectConfigInterface $projectConfiguration,
        TaskFactory $taskFactory,
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

    public function getTaskFactory(): TaskFactory
    {
        return $this->taskFactory;
    }

    public function getBuildTempDir(): string
    {
        return $this->tempDirectory;
    }
}