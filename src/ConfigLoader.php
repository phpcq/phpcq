<?php

declare(strict_types=1);

namespace Phpcq\Runner;

use Phpcq\Runner\Config\PhpcqConfiguration;
use Phpcq\Runner\Config\PhpcqConfigurationBuilder;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

use function array_key_exists;
use function array_keys;

/**
 * @psalm-import-type TPlugin from \Phpcq\Runner\Config\PhpcqConfiguration
 * @psalm-type TTaskConfig = null|list<string>|array{
 *   directories?: array<string, array|null|bool>,
 *   ...
 * }
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

        $configBuilder = new PhpcqConfigurationBuilder();
        /** @psalm-suppress MixedArgument */
        $processed = $configBuilder->processConfig($config['phpcq']);
        unset($config['phpcq']);
        $processed = array_merge($processed, $config);
        /** @var TConfig $processed */

        // Support simplified chain plugin configuration
        foreach ($processed['tasks'] ?? [] as $task => $taskConfig) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (is_array($taskConfig) && $taskConfig === array_values($taskConfig)) {
                $processed['tasks'][$task] = [
                    'plugin' => 'chain',
                    'config' => [
                        'tasks' => $taskConfig
                    ]
                ];
            }
        }

        // Define default task if not defined
        if (!array_key_exists('default', $processed['tasks'])) {
            $processed['tasks']['default'] = [
                'plugin' => 'chain',
                'config' => [
                    'tasks' => array_keys($processed['tasks'])
                ]
            ];
        }

        return PhpcqConfiguration::fromArray($processed);
    }
}
