<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use IteratorAggregate;
use Phpcq\Exception\ToolNotFoundException;

final class RepositoryPool implements IteratorAggregate, RepositoryInterface
{
    /**
     * @var RepositoryInterface[]
     */
    private $repositories = [];

    public function addRepository(RepositoryInterface $repository)
    {
        $this->repositories[] = $repository;
    }

    public function hasTool(string $name, string $versionConstraint): bool
    {
        foreach ($this as $repository) {
            if ($repository->hasTool($name, $versionConstraint)) {
                return true;
            }
        }
        return false;
    }

    public function getTool(string $name, string $versionConstraint): ToolInformationInterface
    {
        foreach ($this as $repository) {
            if ($repository->hasTool($name, $versionConstraint)) {
                return $repository->getTool($name, $versionConstraint);
            }
        }

        throw new ToolNotFoundException($name, $versionConstraint);
    }

    /**
     * Iterate over all repositories.
     *
     * @return RepositoryInterface[]|iterable
     */
    public function getIterator(): iterable
    {
        yield from $this->repositories;
    }
}
