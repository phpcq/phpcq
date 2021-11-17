<?php

declare(strict_types=1);

namespace Phpcq\Runner\Plugin;

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;

final class ChainPlugin implements ConfigurationPluginInterface
{
    public const VERSION = '1.0.0';

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
