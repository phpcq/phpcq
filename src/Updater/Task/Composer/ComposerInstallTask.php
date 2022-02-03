<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Composer;

use Phpcq\Runner\Updater\UpdateContext;

final class ComposerInstallTask extends AbstractComposerTask
{
    public function getPurposeDescription(): string
    {
        return sprintf('Will install composer dependencies of plugin %s', $this->getPluginName());
    }

    public function getExecutionDescription(): string
    {
        return 'Installing composer dependencies of plugin ' . $this->getPluginName();
    }

    public function execute(UpdateContext $context): void
    {
        if ($this->clearIfComposerNotRequired($context)) {
            return;
        }

        $this->dumpComposerJson($context);
        $context->composer->installDependencies($this->getTargetDirectory($context));
        $this->updateComposerLock($context);
    }
}
