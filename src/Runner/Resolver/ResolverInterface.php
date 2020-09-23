<?php

declare(strict_types=1);

namespace Phpcq\Runner\Resolver;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

interface ResolverInterface
{
    public function resolvePluginVersion(string $pluginName, string $versionConstraint): PluginVersionInterface;

    public function resolveToolVersion(string $pluginName, string $toolName, string $versionConstraint): ToolVersionInterface;
}
