<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Tool;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Updater\UpdateContext;

final class UpgradeToolTask extends AbstractInstallingToolTask
{
    /** @var ToolVersionInterface */
    private $oldToolVersion;

    public function __construct(
        PluginVersionInterface $pluginVersion,
        ToolVersionInterface $toolVersion,
        ToolVersionInterface $oldToolVersion,
        bool $signed
    ) {
        parent::__construct($pluginVersion, $toolVersion, $signed);

        $this->oldToolVersion = $oldToolVersion;
    }

    public function getPurposeDescription(): string
    {
        /** @psalm-suppress RedundantCondition - We experience different behaviour using or not using default branch */
        switch (version_compare($this->oldToolVersion->getVersion(), $this->toolVersion->getVersion())) {
            case 1:
                return 'Will downgrade tool ' . $this->toolVersion->getName() . ' from version '
                    . $this->oldToolVersion->getVersion()
                    . ' to version ' . $this->toolVersion->getVersion();
            case -1:
                return 'Will upgrade tool ' . $this->toolVersion->getName() . ' from version '
                    . $this->oldToolVersion->getVersion()
                    . ' to version ' . $this->toolVersion->getVersion();
            case 0:
            default:
        }

        return 'Will reinstall tool ' . $this->toolVersion->getName() . ' in version '
            . $this->toolVersion->getVersion();
    }

    public function getExecutionDescription(): string
    {
        /** @psalm-suppress RedundantCondition - We experience different behaviour using or not using default branch */
        switch (version_compare($this->oldToolVersion->getVersion(), $this->toolVersion->getVersion())) {
            case 1:
                return 'Downgrading tool ' . $this->toolVersion->getName() . ' from version '
                    . $this->oldToolVersion->getVersion()
                    . ' to version ' . $this->toolVersion->getVersion();
            case -1:
                return 'Upgrading tool ' . $this->toolVersion->getName() . ' from version '
                    . $this->oldToolVersion->getVersion()
                    . ' to version ' . $this->toolVersion->getVersion();
            case 0:
            default:
        }

        return 'Reinstalling tool ' . $this->toolVersion->getName() . ' in version '
            . $this->toolVersion->getVersion();
    }

    public function execute(UpdateContext $context): void
    {
        if ($url = $this->oldToolVersion->getPharUrl()) {
            $context->filesystem->remove($url);
        }
        if ($url = $this->oldToolVersion->getSignatureUrl()) {
            $context->filesystem->remove($url);
        }

        $this->install($context);
    }
}
