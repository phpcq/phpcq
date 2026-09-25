<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Composer;

use Phpcq\Runner\Updater\UpdateContext;

use function sprintf;

final class KeepComposerDependenciesTask extends AbstractComposerTask
{
    #[\Override]
    public function getPurposeDescription(): string
    {
        return sprintf('Will keep composer dependencies of plugin %s', $this->getPluginName());
    }

    #[\Override]
    public function getExecutionDescription(): string
    {
        return 'Keeping composer dependencies of plugin ' . $this->getPluginName();
    }

    #[\Override]
    public function execute(UpdateContext $context): void
    {
        $this->updateComposerLock($context);
    }
}
