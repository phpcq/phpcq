<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Generator;
use Phpcq\Exception\PluginVersionNotFoundException;
use Phpcq\Exception\ToolVersionNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

/**
 * Describes a repository.
 *
 * @extends Traversable<ToolInformationInterface>
 */
interface RepositoryInterface
{
    /**
     * Test if the repository has the passed plugin.
     *
     * @param string $name              The name of the plugin.
     * @param string $versionConstraint The constraint of the plugin (like in composer).
     *
     * @return bool
     */
    public function hasPluginVersion(string $name, string $versionConstraint): bool;

    /**
     * Obtain the plugin with the given name and version constraint.
     *
     * @param string $name              The name of the plugin.
     * @param string $versionConstraint The constraint of the plugin (like in composer).
     *
     * @return PluginVersionInterface
     *
     * @throws PluginVersionNotFoundException
     */
    public function getPluginVersion(string $name, string $versionConstraint): PluginVersionInterface;

    /**
     * Iterate over all plugins.
     *
     * @return Generator|PluginVersionInterface[]
     *
     * @psalm-return Generator<PluginVersionInterface>
     */
    public function iteratePluginVersions(): Generator;

    /**
     * Test if the repository has the passed tool.
     *
     * @param string $name              The name of the tool.
     * @param string $versionConstraint The constraint of the tool (like in composer).
     *
     * @return bool
     */
    public function hasToolVersion(string $name, string $versionConstraint): bool;

    /**
     * Obtain the tool with the given name and version constraint.
     *
     * @param string $name              The name of the tool.
     * @param string $versionConstraint The constraint of the tool (like in composer).
     *
     * @return ToolVersionInterface
     *
     * @throws ToolVersionNotFoundException
     */
    public function getToolVersion(string $name, string $versionConstraint): ToolVersionInterface;

    /**
     * Iterate over all tools.
     *
     * @return Generator|ToolVersionInterface[]
     *
     * @psalm-return Generator<ToolVersionInterface>
     */
    public function iterateToolVersions(): Generator;
}
