<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Phpcq\PluginApi\Version10\InvalidConfigException;

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

    /**
     * @psalm-param array<int, string|null> $repositories
     *
     * @param array $repositories
     */
    public function buildPool(array $repositories): RepositoryPool
    {
        $pool = new RepositoryPool();

        foreach ($repositories as $repository) {
            if (!is_string($repository)) {
                throw new InvalidConfigException('Repository has to be a string');
                // TODO: handle different repository types here.
            }

            $pool->addRepository(new RemoteRepository($repository, $this->repositoryLoader));
        }

        return $pool;
    }
}
