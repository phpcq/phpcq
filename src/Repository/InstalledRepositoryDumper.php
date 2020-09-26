<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use stdClass;

use function json_encode;

final class InstalledRepositoryDumper extends AbstractDumper
{
    public function dump(InstalledRepository $repository, string $fileName): void
    {
        $this->filesystem->dumpFile(
            $fileName,
            json_encode($this->dumpRepository($repository, dirname($fileName)), AbstractDumper::JSON_OPTIONS)
        );
    }

    private function dumpRepository(InstalledRepository $repository, string $baseDir): array
    {
        return [
            'plugins' => $this->dumpInstalledPlugins($repository, $baseDir),
            'tools'   => $this->dumpInstalledTools($repository, $baseDir)
        ];
    }

    private function dumpInstalledPlugins(InstalledRepository $repository, string $baseDir): array
    {
        $plugins = [];

        /** @var InstalledPlugin $plugin */
        foreach ($repository->iteratePlugins() as $plugin) {
            $plugins[$plugin->getPluginVersion()->getName()] = $this->dumpInstalledPlugin($plugin, $baseDir);
        }

        return $plugins;
    }

    private function dumpInstalledTools(InstalledRepository $repository, string $baseDir): array
    {
        $tools = [];

        /** @var ToolVersionInterface $toolVersion */
        foreach ($repository->iterateToolVersions() as $toolVersion) {
            $tools[$toolVersion->getName()] = $this->dumpTool($toolVersion, $baseDir);
        }

        return $tools;
    }

    private function dumpInstalledPlugin(InstalledPlugin $plugin, string $baseDir): array
    {
        $version = $plugin->getPluginVersion();
        $signaturePath = $version->getSignaturePath();

        return [
            'api-version'  => $version->getApiVersion(),
            'version'      => $version->getVersion(),
            'type'         => 'php-file',
            'url'          => $this->getRelativePath($version->getFilePath(), $baseDir),
            'signature'    => $signaturePath ? $this->getRelativePath($signaturePath, $baseDir) : null,
            'requirements' => $this->encodePluginRequirements($version->getRequirements()),
            'checksum'     => $this->encodeHash($version->getHash()),
            'tools'        => $this->dumpTools($plugin, $baseDir),
        ];
    }

    private function dumpTool(ToolVersionInterface $version, string $baseDir): array
    {
        $signatureUrl = $version->getSignatureUrl();
        $pharUrl      = $version->getPharUrl();

        return [
            'version'      => $version->getVersion(),
            'url'          => $pharUrl ? $this->getRelativePath($pharUrl, $baseDir) : null,
            'requirements' => $this->encodeToolRequirements($version->getRequirements()),
            'checksum'     => $this->encodeHash($version->getHash()),
            'signature'    => $signatureUrl ? $this->getRelativePath($signatureUrl, $baseDir) : null,
        ];
    }

    private function dumpTools(InstalledPlugin $plugin, string $baseDir): stdClass
    {
        $tools = new stdClass();
        foreach ($plugin->iterateTools() as $toolVersion) {
            $tools->{$toolVersion->getName()} = $this->dumpTool($toolVersion, $baseDir);
        }

        return $tools;
    }

    private function getRelativePath(string $path, string $baseDir): string
    {
        if (strpos($path, $baseDir) === 0) {
            return substr($path, strlen($baseDir) + 1);
        }

        return $path;
    }
}
