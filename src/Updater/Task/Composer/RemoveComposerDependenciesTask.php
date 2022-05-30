<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Composer;

use Phpcq\Runner\Updater\UpdateContext;

final class RemoveComposerDependenciesTask extends AbstractComposerTask
{
    public function getPurposeDescription(): string
    {
        return 'Will remove composer dependencies of plugin ' . $this->getPluginName();
    }

    public function getExecutionDescription(): string
    {
        return 'Removing composer dependencies of plugin ' . $this->getPluginName();
    }

    public function execute(UpdateContext $context): void
    {
        $context->filesystem->remove(
            [
                $this->locatePath($context, 'vendor'),
                $this->locatePath($context, 'composer.json'),
                $this->locatePath($context, 'composer.lock'),
            ]
        );
        $this->updateComposerLock($context);
    }
}
