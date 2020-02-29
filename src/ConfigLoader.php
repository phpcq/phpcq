<?php

declare(strict_types=1);

namespace Phpcq;

use Phpcq\Config\PhpcqConfiguration;
use Phpcq\Exception\InvalidConfigException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use function array_keys;

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

        if (!isset($config['phpcq'])) {
            throw new InvalidConfigException('Phpcq section missing');
        }

        $processedConfiguration = (new Processor())->processConfiguration(new PhpcqConfiguration(), [$config['phpcq']]);
        unset($config['phpcq']);
        $processedConfiguration = array_merge($processedConfiguration, $config);

        return $this->mergeConfig($processedConfiguration);
    }

    private function mergeConfig(array $config) : array
    {
        foreach (array_keys($config['tools']) as $tool) {
            if (!isset($config[$tool])) {
                $config[$tool] = [];
            }

            if (!isset($config[$tool]['directories'])) {
                $config[$tool]['directories'] = $config['directories'];
                continue;
            }
            foreach ($config['directories'] as $baseDir) {
                if (array_key_exists($baseDir, $config[$tool]['directories'])
                    && false === $config[$tool]['directories'][$baseDir]) {
                    unset($config[$tool]['directories'][$baseDir]);
                    continue;
                }
                $config[$tool]['directories'] = [$baseDir => null] + $config[$tool]['directories'];
            }
        }

        return $config;
    }
}
