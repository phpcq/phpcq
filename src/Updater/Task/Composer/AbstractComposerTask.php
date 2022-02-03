<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Composer;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Updater\Task\UpdateTaskInterface;
use Phpcq\Runner\Updater\UpdateContext;

abstract class AbstractComposerTask implements UpdateTaskInterface
{
    /** @var PluginVersionInterface */
    protected $pluginVersion;

    public function __construct(PluginVersionInterface $pluginVersion)
    {
        $this->pluginVersion = $pluginVersion;
    }

    public function getPluginName(): string
    {
        return $this->pluginVersion->getName();
    }

    protected function updateComposerLock(UpdateContext $context): void
    {
        $context->lockRepository->getPlugin($this->getPluginName())->updateComposerLock(
            $context->composer->getComposerLock($this->pluginVersion)
        );
    }
}
