<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\Runner\Exception\PluginVersionNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;

trait RepositoryHasPluginVersionTrait
{
    public function hasPluginVersion(string $name, string $versionConstraint): bool
    {
        try {
            $this->getPluginVersion($name, $versionConstraint);
            return true;
        } catch (PluginVersionNotFoundException $exception) {
            return false;
        }
    }

    abstract public function getPluginVersion(string $name, string $versionConstraint): PluginVersionInterface;
}
