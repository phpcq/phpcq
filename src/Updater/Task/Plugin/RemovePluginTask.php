<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Plugin;

use Phpcq\Runner\Updater\UpdateContext;

use function sprintf;

final class RemovePluginTask extends AbstractPluginTask
{
    public function getPurposeDescription(): string
    {
        return sprintf(
            'Will remove plugin %s in version %s',
            $this->pluginVersion->getName(),
            $this->pluginVersion->getVersion()
        );
    }

    public function getExecutionDescription(): string
    {
        return sprintf('Removing %s version %s', $this->pluginVersion->getName(), $this->pluginVersion->getVersion());
    }

    public function execute(UpdateContext $context): void
    {
        $context->filesystem->remove($context->installedPluginPath . '/' . $this->pluginVersion->getName());
    }
}
