<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use stdClass;

use function json_encode;

final class LockFileDumper extends AbstractDumper
{
    public function dump(InstalledRepository $repository, string $fileName): void
    {
        $this->filesystem->dumpFile(
            $fileName,
            json_encode($this->dumpRepository($repository), AbstractDumper::JSON_OPTIONS)
        );
    }

    private function dumpRepository(InstalledRepository $repository): array
    {
        return [
            'plugins' => $this->dumpInstalledPlugins($repository),
            'tools'   => $this->dumpInstalledTools($repository)
        ];
    }

    private function dumpInstalledPlugins(InstalledRepository $repository): array
    {
        $plugins = [];

        foreach ($repository->iteratePlugins() as $plugin) {
            $plugins[$plugin->getPluginVersion()->getName()] = $this->dumpInstalledPlugin($plugin);
        }

        return $plugins;
    }

    private function dumpInstalledTools(InstalledRepository $repository): array
    {
        $tools = [];

        foreach ($repository->iterateToolVersions() as $toolVersion) {
            $tools[$toolVersion->getName()] = $this->dumpTool($toolVersion);
        }

        return $tools;
    }

    private function dumpInstalledPlugin(InstalledPlugin $plugin): array
    {
        $version = $plugin->getPluginVersion();

        return [
            'api-version'  => $version->getApiVersion(),
            'version'      => $version->getVersion(),
            'type'         => 'php-file',
            'url'          => $version->getFilePath(),
            'signature'    => $version->getSignaturePath(),
            'requirements' => $this->encodePluginRequirements($version->getRequirements()),
            'checksum'     => $this->encodeHash($version->getHash()),
            'tools'        => $this->dumpTools($plugin),
            'composerLock' => $plugin->getComposerLock()
        ];
    }

    private function dumpTool(ToolVersionInterface $version): array
    {
        return [
            'version'      => $version->getVersion(),
            'url'          => $version->getPharUrl(),
            'requirements' => $this->encodeToolRequirements($version->getRequirements()),
            'checksum'     => $this->encodeHash($version->getHash()),
            'signature'    => $version->getSignatureUrl(),
        ];
    }

    private function dumpTools(InstalledPlugin $plugin): stdClass
    {
        $tools = new stdClass();
        foreach ($plugin->iterateTools() as $toolVersion) {
            $tools->{$toolVersion->getName()} = $this->dumpTool($toolVersion);
        }

        return $tools;
    }
}
