<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Exception\RuntimeException;
use Phpcq\Platform\PlatformRequirementChecker;
use Phpcq\Repository\InstalledRepositoryLoader;
use Phpcq\Repository\Repository;
use Phpcq\Repository\RepositoryInterface;

trait InstalledRepositoryLoadingCommandTrait
{
    private function getInstalledRepository(bool $failIfNotExist): RepositoryInterface
    {
        $requirementChecker = null;
        if (!$this->input->hasOption('ignore-platform-reqs') || !$this->input->getOption('ignore-platform-reqs')) {
            $requirementChecker = PlatformRequirementChecker::create();
        }

        if (!is_file($this->phpcqPath . '/installed.json')) {
            if (!$failIfNotExist) {
                return new Repository($requirementChecker);
            }
            throw new RuntimeException('Please install the tools first ("phpcq update").');
        }
        $loader = new InstalledRepositoryLoader($requirementChecker);

        return $loader->loadFile($this->phpcqPath . '/installed.json');
    }
}
