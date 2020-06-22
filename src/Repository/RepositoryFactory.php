<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use function array_key_exists;
use function assert;
use function is_string;

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
     * @psalm-param list<array{type:string, url?: string}> $repositories
     */
    public function buildPool(array $repositories): RepositoryPool
    {
        $pool = new RepositoryPool();

        foreach ($repositories as $repository) {
            switch ($repository['type']) {
                case 'remote':
                    assert(is_string($url = $repository['url'] ?? null));
                    $pool->addRepository(new RemoteRepository($url, $this->repositoryLoader));
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
