<?php

namespace Phpcq\Plugin;

use IteratorAggregate;
use Phpcq\Exception\RuntimeException;
use Symfony\Component\Finder\Finder;
use function assert;
use function get_class;

final class PluginRegistry implements IteratorAggregate
{
    /** @var array<string, PluginInterface> */
    private $plugins = [];

    public static function buildFromPath(string $pluginPath): self
    {
        $instance = new self();

        foreach (Finder::create()->in($pluginPath)->name('*.php')->getIterator() as $fileInfo) {
            /**
             * @psalm-suppress UnresolvableInclude
             *
             * @var PluginInterface
             */
            $plugin = require $fileInfo->getRealPath();
            if (!$plugin instanceof PluginInterface) {
                throw new RuntimeException('Not a valid plugin: ' . get_class($plugin));
            }

            /** @var string */
            $name = $plugin->getName();
            if (isset($instance->plugins[$name])) {
                throw new RuntimeException('Plugin already registered: ' . $name);
            }
            $instance->plugins[$name] = $plugin;
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
     *
     * @psalm-return \Generator<string, PluginInterface>
     */
    public function getIterator(): iterable
    {
        yield from $this->plugins;
    }
}
