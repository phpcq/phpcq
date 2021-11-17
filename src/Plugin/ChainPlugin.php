<?php

declare(strict_types=1);

namespace Phpcq\Runner\Plugin;

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\Runner\Repository\InstalledPlugin;

final class ChainPlugin implements ConfigurationPluginInterface
{
    private const VERSION = '1.0.0';

    private const API_VERSION = '1.0.0';

    public static function createInstalledPlugin(): InstalledPlugin
    {
        return new InstalledPlugin(
            new PhpFilePluginVersion(
                'chain',
                self::VERSION,
                self::API_VERSION,
                null,
                __DIR__ . '/../Resources/plugins/chain-plugin.php',
                null,
                PluginHash::createForFile(__DIR__ . '/../Resources/plugins/chain-plugin.php')
            )
        );
    }

    public function getName(): string
    {
        return 'chain';
    }

    public function describeConfiguration(PluginConfigurationBuilderInterface $configOptionsBuilder): void
    {
        $configOptionsBuilder
            ->describeStringListOption('tasks', 'The list of tasks which are executed when running the task')
            ->isRequired();
    }

    /** @return list<string> */
    public function getTaskNames(PluginConfigurationInterface $pluginConfiguration): array
    {
        return $pluginConfiguration->getStringList('tasks');
    }
}
