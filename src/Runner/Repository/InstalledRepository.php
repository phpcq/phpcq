<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Generator;
use Phpcq\Exception\ToolVersionNotFoundException;
use Phpcq\RepositoryDefinition\Exception\PluginNotFoundException;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

final class InstalledRepository
{
    /** @var array<string, InstalledPlugin> */
    private $plugins = [];

    /** @var array<string, ToolVersionInterface> */
    private $tools = [];

    public function addPlugin(InstalledPlugin $plugin): void
    {
        $this->plugins[$plugin->getName()] = $plugin;
    }

    /**
     * Obtain the information if a plugin ist installed.
     *
     * @param string $name The name of the plugin.
     *
     * @return bool
     */
    public function hasPlugin(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    /**
     * Obtain the plugin with the given name and version constraint.
     *
     * @param string $name he name of the plugin.
     *
     * @return InstalledPlugin
     *
     * @throws PluginNotFoundException
     */
    public function getPlugin(string $name): InstalledPlugin
    {
        if (isset($this->plugins[$name])) {
            return $this->plugins[$name];
        }

        throw new PluginNotFoundException($name);
    }

    /**
     * Iterate over all installed plugins.
     *
     * @return Generator
     *
     * @psalm-return Generator<InstalledPlugin>
     */
    public function iteratePlugins(): Generator
    {
        foreach ($this->plugins as $plugin) {
            yield $plugin;
        }
    }

    /**
     * Obtain the tool with the given name and version constraint.
     *
     * @param string $name The name of the tool.
     *
     * @return ToolVersionInterface
     *
     * @throws ToolVersionNotFoundException
     */
    public function getToolVersion(string $name): ToolVersionInterface
    {
        if (isset($this->tools[$name])) {
            return $this->tools[$name];
        }

        throw new ToolVersionNotFoundException($name);
    }

    /**
     * Iterate over all installed tools.
     *
     * @return Generator
     *
     * @psalm-return Generator<ToolVersionInterface>
     */
    public function iterateToolVersions(): Generator
    {
        foreach ($this->tools as $toolVersion) {
            yield $toolVersion;
        }
    }

    public function addToolVersion(ToolVersionInterface $toolVersion): void
    {
        $this->tools[$toolVersion->getName()] = $toolVersion;
    }
}
