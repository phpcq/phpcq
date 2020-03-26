<?php

declare(strict_types=1);

namespace Phpcq\Plugin;

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Plugin\Config\ConfigOptionsBuilderInterface;
use Phpcq\Task\TaskRunnerInterface;

interface ConfigurationPluginInterface extends PluginInterface
{
    /**
     * Describe available config options.
     *
     * @param ConfigOptionsBuilderInterface $configOptionsBuilder The config options builder.
     */
    public function describeOptions(ConfigOptionsBuilderInterface $configOptionsBuilder) : void;

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
