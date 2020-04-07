<?php

use Phpcq\PluginApi\Version10\BuildConfigInterface;
use Phpcq\PluginApi\Version10\ConfigurationOptionsBuilderInterface;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;
use Phpcq\PluginApi\Version10\InvalidConfigException;
use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\PluginApi\Version10\TaskRunnerInterface;

return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'demo-plugin';
    }

    public function describeOptions(ConfigurationOptionsBuilderInterface $configOptionsBuilder): void
    {
        $configOptionsBuilder->describeStringOption('demo-key', 'Some demo option');
    }

    public function validateConfig(array $config) : void
    {
        if (!isset($config['demo-key'])) {
            throw new InvalidConfigException('Invalid config, missing key demo-key');
        }
    }

    public function processConfig(array $config, BuildConfigInterface $buildConfig) : iterable
    {
        yield new class ($config) implements TaskRunnerInterface {
            private $config;

            public function __construct(array $config)
            {
                $this->config = $config;
            }

            public function run(OutputInterface $output) : void
            {
                $output->writeln(var_export($this->config, true));
            }
        };
    }
};
