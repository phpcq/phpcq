<?php

namespace Phpcq\Repository;

use IteratorAggregate;
use Phpcq\FileDownloader;
use Traversable;

/**
 * A remote repository.
 */
class RemoteRepository implements IteratorAggregate, RepositoryInterface
{
    use RepositoryHasToolTrait;

    /**
     * @var 
     */
    private $url;

    /**
     * @var FileDownloader
     */
    private $downloader;

    /**
     * The repository to delegate to when queried.
     *
     * @var Repository
     */
    private $delegate;

    /**
     * @param string         $url        URL to the root information of this repository.
     * @param FileDownloader $downloader The downloader to use.
     */
    public function __construct(string $url, FileDownloader $downloader)
    {
        $this->url        = $url;
        $this->downloader = $downloader;
    }

    public function getTool(string $name, string $versionConstraint): ToolInformationInterface
    {
        if (!$this->delegate) {
            $this->preload();
        }
        return $this->delegate->getTool($name, $versionConstraint);
    }

    /**
     * @return ToolInformationInterface[]|Traversable
     */
    public function getIterator()
    {
        if (!$this->delegate) {
            $this->preload();
        }
        yield from $this->delegate;
    }

    /**
     * Preloads the json to the temp dir and initializes the delegating repository.
     *
     * @return void
     */
    private function preload()
    {
        // FIXME: should we rather inject the loader in this class?
        $loader = new JsonRepositoryLoader($this->downloader);

        $this->delegate = $loader->loadFile($this->url);
    }
}
