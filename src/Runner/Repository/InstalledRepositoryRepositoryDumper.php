<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

final class InstalledRepositoryRepositoryDumper extends AbstractRepositoryDumper
{
    protected function dumpInstalledPlugin(InstalledPlugin $plugin): array
    {
        $version = $plugin->getPluginVersion();

        return [
            'api-version'  => $version->getApiVersion(),
            'version'      => $version->getVersion(),
            'type'         => 'php-file',
            'url'          => $version->getFilePath(),
            'requirements' => $this->encodePluginRequirements($version->getRequirements()),
            'checksum'     => $this->encodeHash($version->getHash()),
            'signature'    => $version->getSignature(),
            'tools'        => $this->dumpTools($plugin),
        ];
    }

    protected function dumpTool(ToolVersionInterface $version): array
    {
        return [
            'version'      => $version->getVersion(),
            'url'          => $version->getPharUrl(),
            'requirements' => $this->encodeToolRequirements($version->getRequirements()),
            'checksum'     => $this->encodeHash($version->getHash()),
            'signature'    => $version->getSignatureUrl(),
        ];
    }

    private function dumpTools(InstalledPlugin $plugin): array
    {
        $tools = [];
        foreach ($plugin->iterateTools() as $toolVersion) {
            $tools[$toolVersion->getName()] = $this->dumpTool($toolVersion);
        }

        return $tools;
    }
}

