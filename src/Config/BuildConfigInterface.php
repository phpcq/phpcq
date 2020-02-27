<?php

declare(strict_types=1);

namespace Phpcq\Config;

use Phpcq\Task\TaskFactory;

interface BuildConfigInterface
{
    /**
     * Get the project configuration.
     *
     * @return ProjectConfigInterface
     */
    public function getProjectConfiguration(): ProjectConfigInterface;

    /**
     * Get registered tasks for the build.
     *
     * @return TaskFactory
     */
    public function getTaskFactory(): TaskFactory;

    /**
     * Get the temporary dir for the current build.
     *
     * @return string
     */
    public function getBuildTempDir(): string;
}
