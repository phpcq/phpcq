<?php

declare(strict_types=1);

namespace Phpcq\Plugin;

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Exception\InvalidConfigException;
use Phpcq\Task\TaskRunnerInterface;

interface ConfigurationPluginInterface extends PluginInterface
{
    /**
     * Validate configuration for current plugin.
     *
     * @param array $config The plugin configuration.
     *
     * @return void
     *
     * @throws InvalidConfigException
     */
    public function validateConfig(array $config): void;

    /**
     * Process plugin configuration and create task runners.
     *
     * @param array                $config      The plugin configuration.
     * @param BuildConfigInterface $buildConfig The build configuration.
     *
     * @return TaskRunnerInterface[]
     */
    public function processConfig(array $config, BuildConfigInterface $buildConfig): iterable;
}
