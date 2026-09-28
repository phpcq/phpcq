<?php

declare(strict_types=1);

namespace Phpcq\Runner;

use Phpcq\Runner\Config\PhpcqConfiguration;
use Phpcq\Runner\Config\PhpcqConfigurationBuilder;
use Phpcq\Runner\Config\TasksConfigBuilder;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

use function array_merge;

/**
 * @psalm-import-type TPlugin from \Phpcq\Runner\Config\PhpcqConfiguration
 * @psalm-import-type TTaskConfig from \Phpcq\Runner\Config\PhpcqConfiguration
 * @psalm-type TConfig = array{
 *   repositories: list<string>,
 *   directories: list<string>,
 *   artifact: string,
 *   plugins: array<string,TPlugin>,
 *   trusted-keys: list<string>,
 *   tasks: array<string,TTaskConfig>,
 *   auth: array
 * }
 */
final class ConfigLoader
{
    /**
     * Load configuration from yaml file and return a preprocessed configuration.
     *
     * @param string $configPath Path of the yaml configuration file.
     */
    public static function load(string $configPath): PhpcqConfiguration
    {
        return (new self($configPath))->getConfig();
    }

    public function __construct(private readonly string $configPath)
    {
    }

    public function getConfig(): PhpcqConfiguration
    {
        /** @var array */
        $config = Yaml::parseFile($this->configPath);

        if (!isset($config['phpcq'])) {
            throw new InvalidConfigurationException('Phpcq section missing');
        }

        /** @psalm-suppress MixedArgument */
        $processed = (new PhpcqConfigurationBuilder())->processConfig($config['phpcq']);
        $processed['tasks'] = (new TasksConfigBuilder())->processConfig($config['tasks'] ?? []);
        unset($config['phpcq'], $config['tasks']);
        /** @var TConfig $processed */
        $processed = array_merge($processed, $config);

        return PhpcqConfiguration::fromArray($processed);
    }
}
