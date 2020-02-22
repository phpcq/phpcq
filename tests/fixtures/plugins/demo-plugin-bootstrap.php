<?php

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Exception\InvalidConfigException;
use Phpcq\Plugin\ConfigurationPluginInterface;
use Phpcq\Task\TaskRunnerInterface;
use Symfony\Component\Console\Output\OutputInterface;

return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'demo-plugin';
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
