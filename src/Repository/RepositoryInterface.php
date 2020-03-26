<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use IteratorAggregate;
use Phpcq\Exception\ToolNotFoundException;
use Traversable;

/**
 * Describes a repository.
 */
interface RepositoryInterface extends Traversable
{
    /**
     * Test if the repository has the passed tool.
     *
     * @param string $name              The name of the tool.
     * @param string $versionConstraint The constraint of the tool (like in composer).
     *
     * @return bool
     */
    public function hasTool(string $name, string $versionConstraint): bool;

    /**
     * Obtain the tool with the given name.
     *
     * @param string $name              The name of the tool.
     * @param string $versionConstraint The constraint of the tool (like in composer).
     *
     * @return ToolInformationInterface
     *
     * @throws ToolNotFoundException
     */
    public function getTool(string $name, string $versionConstraint): ToolInformationInterface;
}
