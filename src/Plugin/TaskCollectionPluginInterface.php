<?php

declare(strict_types=1);

namespace Phpcq\Runner\Plugin;

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\ConfigurationPluginInterface;

interface TaskCollectionPluginInterface extends ConfigurationPluginInterface
{
    /** @return list<string> */
    public function getTaskNames(PluginConfigurationInterface $pluginConfiguration): array;
}
