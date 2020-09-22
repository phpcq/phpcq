<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Exception\RuntimeException;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\InstalledRepositoryLoader;

use function is_file;

trait InstalledRepositoryLoadingCommandTrait
{
    protected function getInstalledRepository(bool $failIfNotExist): InstalledRepository
    {
        if (!is_file($this->phpcqPath . '/installed.json')) {
            if (!$failIfNotExist) {
                return new InstalledRepository();
            }
            throw new RuntimeException('Please install the tools first ("phpcq update").');
        }

        return (new InstalledRepositoryLoader())->loadFile($this->phpcqPath . '/installed.json');
    }
}
