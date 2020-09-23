<?php

declare(strict_types=1);

namespace Phpcq\Runner\Resolver;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

/**
 * The resolver is responsible to retrieve a specific version of a plugin and tool.
 */
interface ResolverInterface
{
    /**
     * Resolve a plugin in a specific version.
     *
     * @param string $pluginName        The plugin name.
     * @param string $versionConstraint The version constraint.
     *
     * @return PluginVersionInterface
     */
    public function resolvePluginVersion(string $pluginName, string $versionConstraint): PluginVersionInterface;

    /**
     * Resolve a tool version for a specific plugin.
     *
     * @param string $pluginName        The name of the plugin.
     * @param string $toolName          The tool name.
     * @param string $versionConstraint The version constraint for the tool.
     * @return ToolVersionInterface
     */
    public function resolveToolVersion(
        string $pluginName,
        string $toolName,
        string $versionConstraint
    ): ToolVersionInterface;
}
