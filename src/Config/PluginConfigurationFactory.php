<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config;

use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\PluginApi\Version10\EnricherPluginInterface;
use Phpcq\Runner\Config\Builder\PluginConfigurationBuilder;
use Phpcq\Runner\Environment;
use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\Runner\Repository\InstalledRepository;

use function array_splice;
use function dirname;

/**
 * @psalm-import-type TTaskConfig from PhpcqConfiguration
 */
final class PluginConfigurationFactory
{
    public function __construct(
        private readonly PhpcqConfiguration $phpcqConfiguration,
        private readonly PluginRegistry $plugins,
        private readonly InstalledRepository $installedRepository
    ) {
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

        return $this->createConfiguration($plugin, $environment, $taskConfig);
    }

    /**
     * @param TTaskConfig $taskConfig
     */
    private function createConfiguration(
        ConfigurationPluginInterface $plugin,
        Environment $environment,
        array $taskConfig
    ): PluginConfiguration {
        $configOptionsBuilder = new PluginConfigurationBuilder('config', 'Plugin configuration');
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

            try {
                $enricherConfig = $this->createConfiguration(
                    $enricher,
                    $environment,
                    ['config' => $enricherConfig ?? []]
                );
            } catch (ConfigurationValidationErrorException $exception) {
                // Replace the "config" key, which starts the path of the exception, with the enricher path.
                $path = $exception->getPath();
                array_splice($path, 0, 1, ['uses', $enricherName]);
                throw ConfigurationValidationErrorException::fromError($path, $exception->getRootError(), $exception);
            }
            $pluginConfig   = $enricher->enrich(
                $plugin->getName(),
                $installedVersion->getVersion(),
                $pluginConfig,
                $enricherConfig,
                $enricherEnvironment
            );
        }

        /** @var array<string,mixed> $processed */
        $processed = $configOptionsBuilder->normalizeValue($pluginConfig);
        $configOptionsBuilder->validateValue($processed);

        return new PluginConfiguration($processed);
    }
}
