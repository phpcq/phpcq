<?php

namespace Phpcq\Plugin;

use IteratorAggregate;
use Phpcq\Exception\RuntimeException;
use Symfony\Component\Finder\Finder;
use function get_class;

final class PluginRegistry implements IteratorAggregate
{
    private $plugins = [];

    public static function buildFromPath(string $pluginPath): self
    {
        $instance = new self();

        foreach (Finder::create()->in($pluginPath)->name('*.php')->getIterator() as $fileInfo) {
            if (!($plugin = require $fileInfo->getRealPath()) instanceof PluginInterface) {
                throw new RuntimeException('Not a valid plugin: ' . get_class($plugin));
            }
            if (isset($instance->plugins[$plugin->getName()])) {
                throw new RuntimeException('Plugin already registered: ' . $plugin->getName());
            }
            $instance->plugins[$plugin->getName()] = $plugin;
        }

        return $instance;
    }

    public function getPluginByName(string $name): PluginInterface
    {
        if (!isset($this->plugins[$name])) {
            throw new RuntimeException('Plugin not registered: ' . $name);
        }
        return $this->plugins[$name];
    }

    /**
     * @return PluginInterface[]|iterable
     */
    public function getIterator(): iterable
    {
        yield from $this->plugins;
    }
}
