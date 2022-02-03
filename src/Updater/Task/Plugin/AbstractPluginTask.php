<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Updater\Task\AbstractTask;
use Phpcq\Runner\Updater\UpdateContext;

abstract class AbstractPluginTask extends AbstractTask
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

    public function addPlugin(
        UpdateContext $context,
        PluginVersionInterface $pluginVersion,
        string $bootstrapFile,
        ?string $signaturePath
    ): void {
        $context->installedRepository->addPlugin(
            new InstalledPlugin(
                new PhpFilePluginVersion(
                    $pluginVersion->getName(),
                    $pluginVersion->getVersion(),
                    $pluginVersion->getApiVersion(),
                    $pluginVersion->getRequirements(),
                    $bootstrapFile,
                    $signaturePath,
                    $pluginVersion->getHash()
                )
            )
        );
        $context->lockRepository->addPlugin(new InstalledPlugin($pluginVersion, []));
    }
}
