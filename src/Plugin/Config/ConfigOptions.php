<?php

declare(strict_types=1);

namespace Phpcq\Plugin\Config;

use ArrayIterator;
use Phpcq\PluginApi\Version10\ConfigurationOptionInterface;
use Phpcq\PluginApi\Version10\ConfigurationOptionsInterface;
use Phpcq\PluginApi\Version10\InvalidConfigException;
use Traversable;
use function array_diff_key;
use function array_keys;
use function implode;

final class ConfigOptions implements ConfigurationOptionsInterface
{
    /**
     * @var array<string, ConfigurationOptionInterface>
     */
    private $options;

    /**
     * ConfigOptions constructor.
     *
     * @param array<string, ConfigurationOptionInterface> $options
     */
    public function __construct(array $options)
    {
        $this->options = [];

        foreach ($options as $option) {
            $this->options[$option->getName()] = $option;
        }
    }

    /**
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->options);
    }

    public function count(): int
    {
        return count($this->options);
    }

    /**
     * @param array $config
     *
     * @throws InvalidConfigException When configuration is not valid.
     */
    public function validateConfig(array $config): void
    {
        // Fixme: We might need a better solution for tasks not supporting the directories config
        if (!isset($this->options['directories'])) {
            unset($config['directories']);
        }

        if ($diff = array_diff_key($config, $this->options)) {
            throw new InvalidConfigException(
                'Unknown config keys encountered: ' . implode(', ', array_keys($diff))
            );
        }

        foreach ($this->options as $option) {
            $option->validateValue($config[$option->getName()] ?? null);
        }
    }
}
