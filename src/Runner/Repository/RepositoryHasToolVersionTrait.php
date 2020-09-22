<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\Exception\ToolVersionNotFoundException;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

trait RepositoryHasToolVersionTrait
{
    public function hasToolVersion(string $name, string $versionConstraint): bool
    {
        try {
            $this->getToolVersion($name, $versionConstraint);
            return true;
        } catch (ToolVersionNotFoundException $exception) {
            return false;
        }
    }

    abstract public function getToolVersion(string $name, string $versionConstraint): ToolVersionInterface;
}
