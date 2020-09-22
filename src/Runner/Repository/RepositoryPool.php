<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use IteratorAggregate;
use Phpcq\Exception\PluginVersionNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;

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
        /** @var RepositoryInterface $repository */
        foreach ($this as $repository) {
            if ($repository->hasPluginVersion($name, $versionConstraint)) {
                return $repository->getPluginVersion($name, $versionConstraint);
            }
        }

        throw new PluginVersionNotFoundException($name, $versionConstraint);
    }

    /**
     * Iterate over all repositories.
     *
     * @return \Generator
     *
     * @psalm-return \Generator<array-key, RepositoryInterface, mixed, void>
     */
    public function getIterator(): iterable
    {
        yield from $this->repositories;
    }
}
