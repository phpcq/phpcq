<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Updater\UpdateContext;

use function assert;
use function sprintf;

final class KeepPluginTask extends AbstractPluginTask
{
    public function __construct(InstalledPlugin $plugin)
    {
        parent::__construct($plugin->getPluginVersion());
    }

    public function getPurposeDescription(): string
    {
        return sprintf(
            'Will keep plugin %s in version %s',
            $this->pluginVersion->getName(),
            $this->pluginVersion->getVersion()
        );
    }

    public function getExecutionDescription(): string
    {
        return sprintf(
            'Keeping plugin %s in version %s',
            $this->pluginVersion->getName(),
            $this->pluginVersion->getVersion()
        );
    }

    public function execute(UpdateContext $context): void
    {
        $version = $this->pluginVersion;
        assert($version instanceof PhpFilePluginVersion);

        $this->addPlugin($context, $version, $version->getFilePath(), $version->getSignaturePath());
    }
}
