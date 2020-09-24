<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use IteratorAggregate;
use Phpcq\Runner\Exception\PluginVersionNotFoundException;
use Phpcq\Runner\Exception\ToolVersionNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

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
        /** @var RepositoryInterface $repository */
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
     * @return \Generator|RepositoryInterface[]
     *
     * @psalm-return \Generator<array-key, RepositoryInterface, mixed, void>
     */
    public function getIterator(): iterable
    {
        yield from $this->repositories;
    }
}
