<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

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
     * @param mixed[] $repositories
     * @psalm-param list<string|mixed> $repositories
     */
    public function buildPool(array $repositories): RepositoryPool
    {
        $pool = new RepositoryPool();

        /** @var string|mixed $repository */
        foreach ($repositories as $repository) {
            switch ($repository['type']) {
                case 'remote':
                    $pool->addRepository(new RemoteRepository($repository, $this->repositoryLoader));
                    break;
                default:
                    throw new InvalidConfigurationException(
                        sprintf('Unsupported repository type "%s"', $repository['type'])
                    );
            }
        }

        return $pool;
    }
}
