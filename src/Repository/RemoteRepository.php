<?php

namespace Phpcq\Repository;

use IteratorAggregate;
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
     * The repository to delegate to when queried.
     *
     * @var RepositoryInterface|null
     */
    private $delegate;

    /**
     * @var JsonRepositoryLoader
     */
    private $repositoryLoader;

    /**
     * @param string $url URL to the root information of this repository.
     * @param JsonRepositoryLoader $repositoryLoader The repository loader loading the repository data.
     */
    public function __construct(string $url, JsonRepositoryLoader $repositoryLoader)
    {
        $this->url        = $url;
        $this->repositoryLoader = $repositoryLoader;
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
     *
     * @psalm-return \Traversable<int, ToolInformationInterface>
     */
    public function getIterator(): Traversable
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
    private function preload(): void
    {
        $this->delegate = $this->repositoryLoader->loadFile($this->url);
    }
}
