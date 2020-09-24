<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;

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
                    $url = ($repository['url'] ?? null);
                    assert(is_string($url));
                    $pool->addRepository($this->repositoryLoader->loadFile($url));
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
