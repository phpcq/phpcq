<?php

declare(strict_types=1);

namespace Phpcq\Runner\Plugin;

use Generator;
use IteratorAggregate;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\PluginInterface;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersionInterface;
use Phpcq\Runner\Repository\InstalledRepository;
use Traversable;

use function assert;
use function get_class;

/**
 * @psalm-import-type TInstalledRepository from \Phpcq\Runner\Repository\InstalledRepositoryLoader
 * @implements IteratorAggregate<string, PluginInterface>
 */
final class PluginRegistry implements IteratorAggregate
{
    /** @var array<string, PluginInterface> */
    private $plugins = [];

    public static function buildFromInstalledRepository(InstalledRepository $repository): self
    {
        $instance = new self();

        foreach ($repository->iteratePlugins() as $plugin) {
            $pluginVersion = $plugin->getPluginVersion();
            assert($pluginVersion instanceof PhpFilePluginVersionInterface);
            $instance->loadPluginFile($pluginVersion->getFilePath());
        }

        return $instance;
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function loadPluginFile(string $filePath): void
    {
        /** @psalm-suppress UnresolvableInclude */
        $plugin = require $filePath;
        assert(is_object($plugin));
        if (!$plugin instanceof PluginInterface) {
            throw new RuntimeException('Not a valid plugin: ' . $plugin::class);
        }

        $name = $plugin->getName();
        if (isset($this->plugins[$name])) {
            throw new RuntimeException('Plugin already registered: ' . $name);
        }
        $this->plugins[$name] = $plugin;
    }

    public function getPluginByName(string $name): PluginInterface
    {
        if (!isset($this->plugins[$name])) {
            throw new RuntimeException('Plugin not registered: ' . $name);
        }
        return $this->plugins[$name];
    }

    /**
     * @return Generator<string, PluginInterface, mixed, void>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        yield from $this->plugins;
    }

    /**
     * @param class-string<T> $pluginType
     *
     * @template T
     *
     * @return Generator<string, T, mixed, void>
     */
    public function getByType(string $pluginType): iterable
    {
        foreach ($this->getIterator() as $plugin) {
            if ($plugin instanceof $pluginType) {
                yield $plugin->getName() => $plugin;
            }
        }
    }
}
