<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Repository\JsonRepositoryLoader;
use Phpcq\Repository\RemoteRepository;
use Phpcq\Repository\RepositoryInterface;

trait LockFileRepositoryTrait
{
    protected function getLockFileName(): string
    {
        return getcwd() . '/.phpcq.lock';
    }

    protected function loadLockFileRepository(JsonRepositoryLoader $repositoryLoader): ?RepositoryInterface
    {
        $lockFile = $this->getLockFileName();
        if (!file_exists($lockFile)) {
            return null;
        }

        return new RemoteRepository($lockFile, $repositoryLoader);
    }
}
