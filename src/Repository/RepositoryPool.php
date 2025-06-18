<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Generator;
use IteratorAggregate;
use Override;
use Phpcq\Runner\Exception\PluginVersionNotFoundException;
use Phpcq\Runner\Exception\ToolVersionNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Traversable;

/** @implements IteratorAggregate<array-key, RepositoryInterface> */
final class RepositoryPool implements IteratorAggregate
{
    /**
     * @var RepositoryInterface[]
     */
    private $repositories = [];

    public function addRepository(RepositoryInterface $repository): void
    {
        $this->repositories[] = $repository;
    }

    public function hasPlugin(string $name, string $versionConstraint): bool
    {
        foreach ($this as $repository) {
            if ($repository->hasPluginVersion($name, $versionConstraint)) {
                return true;
            }
        }
        return false;
    }

    public function getPluginVersion(string $name, string $versionConstraint): PluginVersionInterface
    {
        foreach ($this as $repository) {
            if ($repository->hasPluginVersion($name, $versionConstraint)) {
                return $repository->getPluginVersion($name, $versionConstraint);
            }
        }

        throw new PluginVersionNotFoundException($name, $versionConstraint);
    }

    public function getToolVersion(string $name, string $versionConstraint): ToolVersionInterface
    {
        foreach ($this as $repository) {
            if ($repository->hasToolVersion($name, $versionConstraint)) {
                return $repository->getToolVersion($name, $versionConstraint);
            }
        }

        throw new ToolVersionNotFoundException($name, $versionConstraint);
    }

    /**
     * Iterate over all repositories.
     *
     * @return Generator<array-key, RepositoryInterface, mixed, void>
     */
    #[Override]
    public function getIterator(): Traversable
    {
        yield from $this->repositories;
    }
}
