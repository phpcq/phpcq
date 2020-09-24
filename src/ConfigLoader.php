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
 * @psalm-import-type TConfig from \Phpcq\Runner\Config\PhpcqConfiguration
 * @psalm-type TTaskConfig = array{
 *   directories?: array<string, array|null|bool>
 * }
 */
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
     */
    public static function load(string $configPath): PhpcqConfiguration
    {
        return (new self($configPath))->getConfig();
    }

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function getConfig(): PhpcqConfiguration
    {
        /** @psalm-var array */
        $config = Yaml::parseFile($this->configPath);

        if (!isset($config['phpcq'])) {
            throw new InvalidConfigurationException('Phpcq section missing');
        }

        $configBuilder = new PhpcqConfigurationBuilder();
        /** @psalm-suppress MixedArgument */
        $processed = $configBuilder->processConfig($config['phpcq']);
        unset($config['phpcq']);
        /** @psalm-var TConfig $processed */
        $processed = array_merge($processed, $config);

        if (!array_key_exists('default', $processed['chains'])) {
            $processed['chains']['default'] = array_keys($processed['tasks']);
        }

        return PhpcqConfiguration::fromArray($processed);
    }
}
