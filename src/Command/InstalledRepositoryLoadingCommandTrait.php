<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Exception\RuntimeException;
use Phpcq\Platform\PlatformInformation;
use Phpcq\Repository\InstalledRepositoryLoader;
use Phpcq\Repository\Repository;
use Phpcq\Repository\RepositoryInterface;

trait InstalledRepositoryLoadingCommandTrait
{
    private function getInstalledRepository(
        bool $failIfNotExist
    ): RepositoryInterface {
        if (!is_file($this->phpcqPath . '/installed.json')) {
            if (!$failIfNotExist) {
                return new Repository(PlatformInformation::createFromCurrentPlatform());
            }
            throw new RuntimeException('Please install the tools first ("phpcq update").');
        }
        $loader = new InstalledRepositoryLoader(PlatformInformation::createFromCurrentPlatform());

        return $loader->loadFile($this->phpcqPath . '/installed.json');
    }
}