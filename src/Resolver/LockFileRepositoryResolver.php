<?php

declare(strict_types=1);

namespace Phpcq\Runner\Resolver;

use Composer\Semver\Semver;
use Phpcq\Runner\Exception\PluginVersionNotFoundException;
use Phpcq\Runner\Exception\ToolVersionNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Repository\InstalledRepository;

final class LockFileRepositoryResolver implements ResolverInterface
{
    /** @var InstalledRepository */
    protected $repository;

    public function __construct(InstalledRepository $repository)
    {
        $this->repository = $repository;
    }

    public function resolvePluginVersion(string $pluginName, string $versionConstraint): PluginVersionInterface
    {
        $version = $this->repository->getPlugin($pluginName)->getPluginVersion();
        if (! Semver::satisfies($version->getVersion(), $versionConstraint)) {
            throw new PluginVersionNotFoundException($pluginName, $versionConstraint);
        }

        return $version;
    }

    public function resolveToolVersion(
        string $pluginName,
        string $toolName,
        string $versionConstraint
    ): ToolVersionInterface {
        $plugin  = $this->repository->getPlugin($pluginName);
        $version = $plugin->getTool($toolName);
        if (! Semver::satisfies($version->getVersion(), $versionConstraint)) {
            throw new ToolVersionNotFoundException($toolName, $versionConstraint);
        }

        return $version;
    }
}
