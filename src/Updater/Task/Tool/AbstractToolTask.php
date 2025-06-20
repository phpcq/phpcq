<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Tool;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Updater\Task\TaskInterface;
use Phpcq\Runner\Updater\UpdateContext;

abstract class AbstractToolTask implements TaskInterface
{
    public function __construct(
        protected PluginVersionInterface $pluginVersion,
        protected ToolVersionInterface $toolVersion
    ) {
    }

    #[\Override]
    public function getPluginName(): string
    {
        return $this->pluginVersion->getName();
    }

    protected function addTool(
        UpdateContext $context,
        PluginVersionInterface $pluginVersion,
        ToolVersionInterface $toolVersion,
        ?string $pharName,
        ?string $signaturePath,
        ?ToolHash $hash
    ): void {
        $context->installedRepository->getPlugin($pluginVersion->getName())->addTool(
            new ToolVersion(
                $toolVersion->getName(),
                $toolVersion->getVersion(),
                $pharName,
                clone $toolVersion->getRequirements(),
                $hash,
                $signaturePath
            )
        );

        $context->lockRepository->getPlugin($pluginVersion->getName())->addTool($toolVersion);
    }
}
