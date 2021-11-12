<?php

declare(strict_types=1);

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\Runner\Plugin\TaskCollectionPluginInterface;

return new class implements TaskCollectionPluginInterface {
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

    public function getTaskNames(PluginConfigurationInterface $pluginConfiguration): array
    {
        return $pluginConfiguration->getStringList('tasks');
    }
};
