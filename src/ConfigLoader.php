<?php

declare(strict_types=1);

namespace Phpcq;

use Symfony\Component\Yaml\Yaml;

final class ConfigLoader
{
    /**
     * @var string
     */
    private $configPath;

    /**
     * Load configuration from yaml file and return a preprocessed configuration.
     *
     * @param string $configPath Path of the yaml configuration file.
     *
     * @return mixed[][]
     */
    public static function load(string $configPath): array
    {
        return (new self($configPath))->getConfig();
    }

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function getConfig(): array
    {
        $config = Yaml::parseFile($this->configPath);

        // TODO: Valid phpcq section of configuration

        return $this->mergeConfig($config);
    }

    private function mergeConfig($config) : array
    {
        // TODO: Merge directories to sub configs.
        // TODO: Create empty task config if missing

        return $config;
    }
}
