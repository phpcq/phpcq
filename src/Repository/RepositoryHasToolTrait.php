<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Phpcq\Exception\ToolNotFoundException;

trait RepositoryHasToolTrait
{
    public function hasTool(string $name, string $versionConstraint): bool
    {
        try {
            $this->getTool($name, $versionConstraint);
            return true;
        } catch (ToolNotFoundException $exception) {
            return false;
        }
    }

    abstract public function getTool(string $name, string $versionConstraint): ToolInformationInterface;
}
