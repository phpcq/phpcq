<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Tool;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Updater\Task\HashValidator;
use Phpcq\Runner\Updater\UpdateContext;

abstract class AbstractInstallingToolTask extends AbstractToolTask
{
    use HashValidator;

    /** @var bool */
    private $signed;

    public function __construct(PluginVersionInterface $pluginVersion, ToolVersionInterface $toolVersion, bool $signed)
    {
        parent::__construct($pluginVersion, $toolVersion);

        $this->signed = $signed;
    }

    protected function install(UpdateContext $context): void
    {
        $pharName      = null;
        $hash          = null;
        $signatureName = null;

        if ($pharUrl = $this->toolVersion->getPharUrl()) {
            $pharName = sprintf(
                '%1$s/tools/%2$s~%3$s.phar',
                $this->getPluginName(),
                $this->toolVersion->getName(),
                $this->toolVersion->getVersion()
            );
            $pharPath = $context->installedPluginPath . '/' . $pharName;

            $context->downloader->downloadFileTo($pharUrl, $pharPath);

            $this->validateHash($pharPath, $this->toolVersion->getHash());
            $signatureName = $this->verifySignature($context, $pharPath);
            $hash = $this->toolVersion->getHash() ?: ToolHash::createForFile($pharPath);
        }

        $this->addTool($context, $this->pluginVersion, $this->toolVersion, $pharName, $signatureName, $hash);
    }

    protected function verifySignature(UpdateContext $context, string $pharPath): ?string
    {
        $signatureUrl = $this->toolVersion->getSignatureUrl();
        if (null === $signatureUrl) {
            if (!$this->signed) {
                return null;
            }

            $context->filesystem->remove($pharPath);

            throw new RuntimeException(
                sprintf(
                    'Install of tool "%s" for plugin "%s" rejected. No signature given. You may have to disable '
                    . 'signature verification for this tool',
                    $this->toolVersion->getName(),
                    $this->pluginVersion->getName()
                )
            );
        }

        $signatureName = sprintf(
            '%1$s/tools/%2$s~%3$s.asc',
            $this->getPluginName(),
            $this->toolVersion->getName(),
            $this->toolVersion->getVersion()
        );
        $signaturePath = $context->installedPluginPath . '/' . $signatureName;
        $context->downloader->downloadFileTo($signatureUrl, $signaturePath);
        $result = $context->signatureVerifier->verify(
            (string) file_get_contents($pharPath),
            (string) file_get_contents($signaturePath)
        );

        if ($this->signed && !$result->isValid()) {
            $context->filesystem->remove($pharPath);
            $context->filesystem->remove($context->installedPluginPath . '/' . $signatureName);

            throw new RuntimeException(
                sprintf(
                    'Install of tool "%s" for plugin "%s" rejected. No signature given. You may have to disable '
                    . 'signature verification for this tool',
                    $this->toolVersion->getName(),
                    $this->getPluginName()
                )
            );
        }

        return $signatureName;
    }
}
