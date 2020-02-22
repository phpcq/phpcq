<?php

declare(strict_types=1);

namespace Phpcq\Config;

/**
 * ProjectConfigInterface describes the global configuration of the current project
 */
interface ProjectConfigInterface
{
    /**
     * Get the root directory of the path.
     *
     * @return string
     */
    public function getProjectRootPath(): string;

    /**
     * Get list of source directories.
     *
     * @return array
     */
    public function getDirectories(): array;

    /**
     * Get the artifact output path.
     *
     * @return string
     */
    public function getArtifactOutputPath(): string;
}
