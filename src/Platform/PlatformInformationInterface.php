<?php

declare(strict_types=1);

namespace Phpcq\Platform;

/**
 * Plattform information contains information about the current php environment
 */
interface PlatformInformationInterface
{
    /**
     * Get the used php version as normalized version string.
     *
     * @return string
     */
    public function getPhpVersion(): string;

    /**
     * Get all loaded extensions mapped with the installed version.
     *
     * @return string[]
     */
    public function getExtensions(): array;

    /**
     * Get all available libraries with the installed version.
     *
     * @return string[]
     */
    public function getLibraries(): array;
}