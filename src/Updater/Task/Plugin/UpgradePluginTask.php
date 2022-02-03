<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersionInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Updater\Task\HashValidator;
use Phpcq\Runner\Updater\UpdateContext;

use function sprintf;

final class UpgradePluginTask extends AbstractInstallingPluginTask
{
    use HashValidator;

    /** @var PluginVersionInterface */
    private $oldPluginVersion;

    /** @var bool */
    private $signed;

    public function __construct(
        PluginVersionInterface $pluginVersion,
        PluginVersionInterface $oldPluginVersion,
        bool $signed
    ) {
        parent::__construct($pluginVersion);

        $this->oldPluginVersion = $oldPluginVersion;
        $this->signed           = $signed;
    }

    public function getPurposeDescription(): string
    {
        /** @psalm-suppress RedundantCondition - We experience different behaviour using or not using default branch */
        switch (version_compare($this->oldPluginVersion->getVersion(), $this->pluginVersion->getVersion())) {
            case 1:
                return 'Will downgrade plugin ' . $this->getPluginName() . ' from version '
                    . $this->oldPluginVersion->getVersion()
                    . ' to version ' . $this->pluginVersion->getVersion();

            case -1:
                return 'Will upgrade plugin ' . $this->getPluginName() . ' from version '
                    . $this->oldPluginVersion->getVersion()
                    . ' to version ' . $this->pluginVersion->getVersion();

            case 0:
            default:
        }

        return 'Will reinstall plugin ' . $this->getPluginName() . ' in version ' . $this->pluginVersion->getVersion();
    }

    public function getExecutionDescription(): string
    {
        /** @psalm-suppress RedundantCondition - We experience different behaviour using or not using default branch */
        switch (version_compare($this->oldPluginVersion->getVersion(), $this->pluginVersion->getVersion())) {
            case 1:
                return 'Downgrading plugin ' . $this->getPluginName() . ' from version '
                    . $this->oldPluginVersion->getVersion()
                    . ' to version ' . $this->pluginVersion->getVersion();

            case -1:
                return 'Upgrading plugin ' . $this->getPluginName() . ' from version '
                    . $this->oldPluginVersion->getVersion()
                    . ' to version ' . $this->pluginVersion->getVersion();

            case 0:
            default:
        }

        return 'Reinstalling plugin ' . $this->getPluginName() . ' in version ' . $this->pluginVersion->getVersion();
    }

    public function execute(UpdateContext $context): void
    {
        if ($this->pluginVersion instanceof PhpFilePluginVersionInterface) {
            $context->filesystem->remove($context->installedPluginPath . '/' . $this->pluginVersion->getFilePath());
        }

        if ($signatureUrl = $this->pluginVersion->getSignaturePath()) {
            $context->filesystem->remove($context->installedPluginPath . '/' . $signatureUrl);
        }

        $bootstrapFile = sprintf('%1$s/plugin.php', $this->pluginVersion->getName());
        $bootstrapPath = $context->installedPluginPath . '/' . $bootstrapFile;

        $context->downloader->downloadFileTo($this->pluginVersion->getFilePath(), $bootstrapPath);
        $this->validateHash($bootstrapPath, $this->pluginVersion->getHash());
        $signatureName = $this->verifySignature($context, $this->pluginVersion, $bootstrapPath, $this->signed);

        $this->addPlugin($context, $this->pluginVersion, $bootstrapFile, $signatureName);
    }
}
