<?php

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Plugin\ConfigurationPluginInterface;

return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'phpunit';
    }

    private static $knownConfigKeys = [
        'custom_flags' => 'custom_flags'
    ];

    public function validateConfig(array $config) : void
    {
        // Fix: we do not use the directories in this task, so we do not want to bail on them.
        unset($config['directories']);
        if ($diff = array_diff_key($config, self::$knownConfigKeys)) {
            throw new \Phpcq\Exception\RuntimeException(
                'Unknown config keys encountered: ' . implode(', ', array_keys($diff))
            );
        }
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
