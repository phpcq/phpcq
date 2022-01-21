<?php

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\EnvironmentInterface;

return new class implements \Phpcq\PluginApi\Version10\EnricherPluginInterface {
    public function getName(): string
    {
        return 'enricher-a';
    }

    public function describeConfiguration(PluginConfigurationBuilderInterface $configOptionsBuilder): void
    {
    }

    public function enrich(
        string $pluginName,
        string $pluginVersion,
        array $pluginConfig,
        PluginConfigurationInterface $config,
        EnvironmentInterface $environment
    ): array {
        $pluginConfig['rulesets'][] = 'enricher-a.xml';

        return $pluginConfig;
    }
};
