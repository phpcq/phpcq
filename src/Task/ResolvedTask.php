<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Phpcq\PluginApi\Version10\PluginInterface;
use Phpcq\Runner\Config\PluginConfiguration;
use Phpcq\Runner\Environment;

/**
 * A configured task with its plugin, configuration and environment, ready to create the tasks to run.
 *
 * @psalm-import-type TTaskConfig from \Phpcq\Runner\Config\PhpcqConfiguration
 */
final class ResolvedTask
{
    /**
     * @param TTaskConfig $taskConfig
     */
    public function __construct(
        public readonly string $name,
        public readonly array $taskConfig,
        public readonly PluginInterface $plugin,
        public readonly PluginConfiguration $configuration,
        public readonly Environment $environment
    ) {
    }
}
