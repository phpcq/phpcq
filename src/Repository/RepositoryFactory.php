<?php

declare(strict_types=1);

namespace Phpcq\Repository;

class RepositoryFactory
{
    /**
     * @var JsonRepositoryLoader
     */
    private $repositoryLoader;

    /**
     * @param JsonRepositoryLoader $repositoryLoader The repository loader to use.
     */
    public function __construct(JsonRepositoryLoader $repositoryLoader)
    {
        $this->repositoryLoader = $repositoryLoader;
    }

    public function buildPool(array $config): RepositoryPool
    {
        $pool = new RepositoryPool();
        if (!isset($config['repositories'])) {
            return $pool;
        }
        foreach ($config['repositories'] as $repository) {
            if (is_string($repository)) {
                $pool->addRepository(new RemoteRepository($repository, $this->repositoryLoader));
                continue;
            }
            // TODO: handle different repository types here.
        }

        return $pool;
    }
}
