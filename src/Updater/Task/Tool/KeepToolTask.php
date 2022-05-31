<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Tool;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Updater\UpdateContext;

final class KeepToolTask extends AbstractToolTask
{
    /** @var ToolVersionInterface */
    private $installedToolVersion;

    public function __construct(
        PluginVersionInterface $pluginVersion,
        ToolVersionInterface $toolVersion,
        ToolVersionInterface $installedToolVersion
    ) {
        parent::__construct($pluginVersion, $toolVersion);
        $this->installedToolVersion = $installedToolVersion;
    }

    public function getPurposeDescription(): string
    {
        return 'Will keep tool ' . $this->toolVersion->getName() . ' in version ' . $this->toolVersion->getVersion();
    }

    public function getExecutionDescription(): string
    {
        return 'Keeping tool ' . $this->toolVersion->getName() . ' in version ' . $this->toolVersion->getVersion();
    }

    public function execute(UpdateContext $context): void
    {
        $this->addTool(
            $context,
            $this->pluginVersion,
            $this->toolVersion,
            $this->installedToolVersion->getPharUrl(),
            $this->installedToolVersion->getSignatureUrl(),
            $this->installedToolVersion->getHash()
        );
    }
}
