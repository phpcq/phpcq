<?php

declare(strict_types=1);

namespace Phpcq\Runner\Resolver;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Repository\RepositoryPool;

final class RepositoryPoolResolver implements ResolverInterface
{
    /** @var RepositoryPool */
    private $pool;

    public function __construct(RepositoryPool $repositoryPool)
    {

        $this->pool = $repositoryPool;
    }

    public function resolvePluginVersion(string $pluginName, string $versionConstraint): PluginVersionInterface
    {
        return $this->pool->getPluginVersion($pluginName, $versionConstraint);
    }

    public function resolveToolVersion(
        string $pluginName,
        string $toolName,
        string $versionConstraint
    ): ToolVersionInterface {
        return $this->pool->getToolVersion($toolName, $versionConstraint);
    }
}
