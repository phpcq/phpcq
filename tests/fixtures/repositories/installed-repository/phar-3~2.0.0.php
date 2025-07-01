<?php

declare(strict_types=1);

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;

return new class implements ConfigurationPluginInterface {
    public function getName(): string
    {
        return 'phar-3';
    }

    public function describeConfiguration(PluginConfigurationBuilderInterface $configOptionsBuilder): void
    {
        $configOptionsBuilder->describeStringListOption('rulesets', 'Plugin rulesets');
    }
};
