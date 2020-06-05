<?php

namespace Phpcq\Plugin;

use Generator;
use IteratorAggregate;
use LogicException;
use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\PluginInterface;
use Phpcq\Repository\InstalledBootstrap;
use Phpcq\Repository\RepositoryInterface;

use function get_class;

final class PluginRegistry implements IteratorAggregate
{
    /** @var array<string, PluginInterface> */
    private $plugins = [];

    public static function buildFromInstalledJson(string $installedJson): self
    {
        $instance = new self();

        foreach (self::getBootstrapFileNames($installedJson) as $filePath) {
            $instance->loadPluginFile($filePath);
        }

        return $instance;
    }

    public static function buildFromInstalledRepository(RepositoryInterface $repository): self
    {
        $instance = new self();

        foreach ($repository as $toolVersion) {
            $bootstrap = $toolVersion->getBootstrap();
            assert($bootstrap instanceof InstalledBootstrap);
            $instance->loadPluginFile($bootstrap->getFilePath());
        }

        return $instance;
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function loadPluginFile(string $filePath): void
    {
        /**
         * @psalm-suppress UnresolvableInclude
         *
         * @var PluginInterface
         */
        $plugin = require_once $filePath;
        if (!$plugin instanceof PluginInterface) {
            throw new RuntimeException('Not a valid plugin: ' . get_class($plugin));
        }

        /** @var string */
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
     * @return PluginInterface[]|iterable
     *
     * @psalm-return Generator<string, PluginInterface, mixed, void>
     */
    public function getIterator(): iterable
    {
        yield from $this->plugins;
    }

    /**
     * @return Generator
     *
     * @psalm-return Generator<int, string, mixed, void>
     */
    private static function getBootstrapFileNames(string $jsonFile): Generator
    {
        $realFile = realpath($jsonFile);
        if (false === $realFile || !is_readable($realFile)) {
            throw new RuntimeException('Invalid path provided: ' . $jsonFile);
        }
        $baseDir  = dirname($realFile);
        $contents = json_decode(file_get_contents($jsonFile), true);
        foreach ($contents['phars'] as $toolName => $toolVersions) {
            if (count($toolVersions) > 1) {
                throw new LogicException('Tool ' . $toolName . ' has multiple versions installed.');
            }

            $version   = $toolVersions[0];
            $bootstrap = $version['bootstrap'];
            if (!is_array($bootstrap) || 'file' !== $bootstrap['type']) {
                throw new RuntimeException('Invalid bootstrap definition: ' . json_encode($bootstrap));
            }
            // Bootstrap files are in the same dir as the installed.json.
            $path = realpath($baseDir . '/' . $bootstrap['url']);
            if (false === $path || !is_readable($path)) {
                throw new RuntimeException(
                    'Invalid bootstrap path provided for tool ' . $toolName . ': ' . $bootstrap['url']
                );
            }

            yield $path;
        }
    }
}
