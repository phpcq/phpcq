<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Updater\UpdateContext;

abstract class AbstractInstallingPluginTask extends AbstractPluginTask
{
    public function verifySignature(
        UpdateContext $context,
        PluginVersionInterface $pluginVersion,
        string $pharPath,
        bool $requireSigned
    ): ?string {
        $signature = $pluginVersion->getSignaturePath();
        if (null === $signature) {
            if (! $requireSigned) {
                return null;
            }

            $context->filesystem->remove($pharPath);

            throw new RuntimeException(
                sprintf(
                    'Install tool "%s" rejected. No signature given. You may have to disable signature verification'
                    . ' for this tool',
                    $pluginVersion->getName(),
                )
            );
        }

        $signatureName = sprintf('%1$s~%2$s.asc', $pluginVersion->getName(), $pluginVersion->getVersion());
        $signaturePath = $context->installedPluginPath . '/' . $pluginVersion->getName() . '/' . $signatureName;
        file_put_contents($signaturePath, $signature);
        $result = $context->signatureVerifier->verify(file_get_contents($pharPath), $signature);

        if ($requireSigned && ! $result->isValid()) {
            $context->filesystem->remove($pharPath);
            $context->filesystem->remove($context->installedPluginPath . '/' . $signatureName);

            throw new RuntimeException(
                sprintf(
                    'Verify signature for tool "%s" failed with key fingerprint "%s"',
                    $pluginVersion->getName(),
                    $result->getFingerprint() ?: 'UNKNOWN'
                )
            );
        }

        return $signatureName;
    }
}
