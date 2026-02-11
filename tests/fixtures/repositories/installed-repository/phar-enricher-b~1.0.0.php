<?php

declare(strict_types=1);

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\EnvironmentInterface;

return new class implements \Phpcq\PluginApi\Version10\EnricherPluginInterface {
    public function getName(): string
    {
        return 'enricher-b';
    }

    public function describeConfiguration(PluginConfigurationBuilderInterface $configOptionsBuilder): void
    {
        $configOptionsBuilder
            ->describeBoolOption('strict', 'Strict mode')
            ->withDefaultValue(false)
            ->isRequired();
    }

    public function enrich(
        string $pluginName,
        string $pluginVersion,
        array $pluginConfig,
        PluginConfigurationInterface $config,
        EnvironmentInterface $environment
    ): array {

        if ($config->getBool('strict')) {
            $pluginConfig['rulesets'][] = 'enricher-b-strict.xml';
        } else {
            $pluginConfig['rulesets'][] = 'enricher-b.xml';
        }

        return $pluginConfig;
    }
};
