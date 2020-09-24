<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\InstalledRepositoryLoader;

use function is_file;

trait InstalledRepositoryLoadingCommandTrait
{
    protected function getInstalledRepository(bool $failIfNotExist): InstalledRepository
    {
        $installedPath = $this->getPluginPath() . '/installed.json';
        if (!is_file($installedPath)) {
            if (!$failIfNotExist) {
                return new InstalledRepository();
            }
            throw new RuntimeException('Please install the tools first ("phpcq update").');
        }

        return (new InstalledRepositoryLoader())->loadFile($installedPath);
    }

    abstract protected function getPluginPath(): string;
}
