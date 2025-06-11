<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Updater\Task\HashValidator;
use Phpcq\Runner\Updater\UpdateContext;

use function sprintf;

final class InstallPluginTask extends AbstractInstallingPluginTask
{
    use HashValidator;

    /** @var bool */
    private $signed;

    public function __construct(PluginVersionInterface $pluginVersion, bool $signed)
    {
        parent::__construct($pluginVersion);

        $this->signed = $signed;
    }

    #[\Override]
    public function getPurposeDescription(): string
    {
        return sprintf(
            'Will install plugin %s in version %s',
            $this->pluginVersion->getName(),
            $this->pluginVersion->getVersion()
        );
    }

    #[\Override]
    public function getExecutionDescription(): string
    {
        return sprintf('Installing %s version %s', $this->pluginVersion->getName(), $this->pluginVersion->getVersion());
    }

    #[\Override]
    public function execute(UpdateContext $context): void
    {
        $bootstrapFile = sprintf('%1$s/plugin.php', $this->pluginVersion->getName());
        $bootstrapPath = $context->installedPluginPath . '/' . $bootstrapFile;

        $context->downloader->downloadFileTo($this->pluginVersion->getFilePath(), $bootstrapPath);
        $this->validateHash($bootstrapPath, $this->pluginVersion->getHash());
        $signatureName = $this->verifySignature($context, $this->pluginVersion, $bootstrapPath, $this->signed);

        $this->addPlugin($context, $this->pluginVersion, $bootstrapFile, $signatureName);
    }
}
