<?php

declare(strict_types=1);

namespace Phpcq;

use Phpcq\Config\PhpcqConfiguration;
use Phpcq\Config\PhpcqConfigurationBuilder;
use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

use function array_fill_keys;
use function array_key_exists;
use function array_keys;

/**
 * @psalm-type TTool = array{
 *    version: string,
 *    signed: bool
 * }
 * @psalm-type TToolConfig = array{
 *   directories?: array<string, array|null|bool>
 * }
 * @psalm-type TConfig = array{
 *   directories: list<string>,
 *   artifact: string,
 *   trusted-keys: list<string>,
 *   chains: array<string,array<string,array|null>>,
 *   tools: array<string,TTool>,
 *   tool-config: array<string,TToolConfig>,
 *   repositories: list<int, string>,
 *   auth: array
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
        $merged = $this->mergeConfig($processed);

        if (!array_key_exists('default', $merged['chains'])) {
            $merged['chains']['default'] = array_fill_keys(array_keys($merged['tools']), null);
        }

        return PhpcqConfiguration::fromArray($merged);
    }

    /**
     * @psalm-param TConfig $config $config
     * @psalm-return TConfig
     */
    private function mergeConfig(array $config): array
    {
        $defaultDirs = [];
        foreach ($config['directories'] as $directory) {
            $defaultDirs[$directory] = null;
        }
        foreach (array_keys($config['tools']) as $tool) {
            if (!isset($config['tool-config'][$tool])) {
                $config['tool-config'][$tool] = [];
            }

            if (!isset($config['tool-config'][$tool]['directories'])) {
                $config['tool-config'][$tool]['directories'] = $defaultDirs;
                continue;
            }
            foreach ($config['directories'] as $baseDir) {
                if (
                    array_key_exists($baseDir, $config['tool-config'][$tool]['directories'])
                    && false === $config['tool-config'][$tool]['directories'][$baseDir]
                ) {
                    unset($config['tool-config'][$tool]['directories'][$baseDir]);
                    continue;
                }
                $config['tool-config'][$tool]['directories'] = [$baseDir => null]
                    + $config['tool-config'][$tool]['directories'];
            }
        }

        return $config;
    }
}
