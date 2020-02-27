<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Phpcq\FileDownloader;

class RepositoryFactory
{
    /**
     * @var FileDownloader
     */
    private $downloader;

    /**
     * @param FileDownloader $downloader The downloader to use.
     */
    public function __construct(FileDownloader $downloader)
    {
        $this->downloader = $downloader;
    }

    public function buildPool(array $config): RepositoryPool
    {
        $pool = new RepositoryPool();
        if (!isset($config['repositories'])) {
            return $pool;
        }
        foreach ($config['repositories'] as $repository) {
            if (is_string($repository)) {
                $pool->addRepository(new RemoteRepository($repository, $this->downloader));
                continue;
            }
            // TODO: handle different repository types here.
        }

        return $pool;
    }
}
