<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Exception\RuntimeException;
use Phpcq\Platform\PlatformInformation;
use Phpcq\Repository\InstalledRepositoryLoader;
use Phpcq\Repository\RepositoryInterface;

trait InstalledRepositoryLoadingCommandTrait
{
    private function getInstalledRepository(string $phpcqPath): RepositoryInterface
    {
        if (!is_file($phpcqPath . '/installed.json')) {
            throw new RuntimeException('Please install the tools first ("phpcq update").');
        }
        $loader = new InstalledRepositoryLoader(PlatformInformation::createFromCurrentPlatform());

        return $loader->loadFile($phpcqPath . '/installed.json');
    }
}