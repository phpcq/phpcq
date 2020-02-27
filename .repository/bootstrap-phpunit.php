<?php

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Plugin\ConfigurationPluginInterface;

return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'phpunit';
    }

    public function validateConfig(array $config) : void
    {
    }

    public function processConfig(array $config, BuildConfigInterface $buildConfig) : iterable
    {
        yield $buildConfig
            ->getTaskFactory()
            ->buildRunPhar('phpunit')
            ->withWorkingDirectory($buildConfig->getProjectConfiguration()->getProjectRootPath())
            ->build();
    }
};
