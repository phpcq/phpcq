<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config;

use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\PluginApi\Version10\EnricherPluginInterface;
use Phpcq\Runner\Config\Builder\PluginConfigurationBuilder;
use Phpcq\Runner\Environment;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\Runner\Repository\InstalledRepository;

use function dirname;

/**
 * @psalm-import-type TTaskConfig from PhpcqConfiguration
 */
final class PluginConfigurationFactory
{
    /** @var PhpcqConfiguration */
    private $phpcqConfiguration;

    /** @var PluginRegistry */
    private $plugins;

    /**
     * @var InstalledRepository
     */
    private $installedRepository;

    public function __construct(
        PhpcqConfiguration $phpcqConfiguration,
        PluginRegistry $plugins,
        InstalledRepository $installedRepository
    ) {
        $this->phpcqConfiguration  = $phpcqConfiguration;
        $this->plugins             = $plugins;
        $this->installedRepository = $installedRepository;
    }

    public function createForTask(string $taskName, Environment $environment): PluginConfiguration
    {
        $taskConfig = $this->phpcqConfiguration->getConfigForTask($taskName);
        $pluginName = $taskConfig['plugin'] ?? $taskName;
        $plugin     = $this->plugins->getPluginByName($pluginName);

        if (!$plugin instanceof ConfigurationPluginInterface) {
            throw new RuntimeException(
                'Plugin "' . $pluginName . '" is not an instance of ConfigurationPluginInterface'
            );
        }

        $configOptionsBuilder = new PluginConfigurationBuilder($plugin->getName(), 'Plugin configuration');
        $plugin->describeConfiguration($configOptionsBuilder);

        return $this->createConfiguration($plugin, $environment, $taskConfig);
    }

    /**
     * @psalm-param TTaskConfig $taskConfig
     */
    private function createConfiguration(
        ConfigurationPluginInterface $plugin,
        Environment $environment,
        array $taskConfig
    ): PluginConfiguration {
        $configOptionsBuilder = new PluginConfigurationBuilder($plugin->getName(), 'Plugin configuration');
        $plugin->describeConfiguration($configOptionsBuilder);

        $pluginConfig = $taskConfig['config'] ?? [];

        if ($configOptionsBuilder->hasDirectoriesSupport()) {
            $pluginConfig += [
                'directories' => $taskConfig['directories'] ?? $this->phpcqConfiguration->getDirectories()
            ];
        }

        foreach ($taskConfig['uses'] ?? [] as $enricherName => $enricherConfig) {
            $enricher = $this->plugins->getPluginByName($enricherName);
            if (!$enricher instanceof EnricherPluginInterface) {
                throw new RuntimeException('Bad configuration. Plugin "' . $enricherName . '" is not an enricher');
            }

            $installedVersion    = $this->installedRepository->getPlugin($enricherName)->getPluginVersion();
            $enricherEnvironment = $environment->withInstalledDir(
                dirname($installedVersion->getFilePath())
            );

            $enricherConfig = $this->createConfiguration($enricher, $environment, ['config' => $enricherConfig ?? []]);
            $pluginConfig   = $enricher->enrich(
                $plugin->getName(),
                $installedVersion->getVersion(),
                $pluginConfig,
                $enricherConfig,
                $enricherEnvironment
            );
        }

        /** @psalm-var array<string,mixed> $processed */
        $processed = $configOptionsBuilder->normalizeValue($pluginConfig);
        $configOptionsBuilder->validateValue($processed);

        return new PluginConfiguration($processed);
    }
}
