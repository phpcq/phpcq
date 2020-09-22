<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\RepositoryDefinition\AbstractHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirementList;
use stdClass;
use Symfony\Component\Filesystem\Filesystem;

use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

abstract class AbstractRepositoryDumper
{
    /** @var int */
    private const JSON_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function dump(InstalledRepository $repository, string $fileName): void
    {
        $this->filesystem->dumpFile(
            $fileName,
            json_encode($this->dumpRepository($repository), self::JSON_OPTIONS)
        );
    }

    protected function dumpRepository(InstalledRepository $repository): array
    {
        return [
            'plugins' => $this->dumpInstalledPlugins($repository),
            'tools'   => $this->dumpInstalledTools($repository)
        ];
    }

    protected function dumpInstalledPlugins(InstalledRepository $repository): array
    {
        $plugins = [];

        /** @var InstalledPlugin $plugin */
        foreach ($repository->iteratePlugins() as $plugin) {
            $plugins[$plugin->getPluginVersion()->getName()] = $this->dumpInstalledPlugin($plugin);
        }

        return $plugins;
    }

    abstract protected function dumpInstalledPlugin(InstalledPlugin $plugin): array;

    protected function dumpInstalledTools(InstalledRepository $repository): array
    {
        $tools = [];

        /** @var ToolVersionInterface $toolVersion */
        foreach ($repository->iterateToolVersions() as $toolVersion) {
            $tools[$toolVersion->getName()] = $this->dumpTool($toolVersion);
        }

        return $tools;
    }

    abstract protected function dumpTool(ToolVersionInterface $version): array;

    protected function encodePluginRequirements(PluginRequirements $requirements): stdClass
    {
        $output = new stdClass();
        foreach (
            [
                'php'      => $requirements->getPhpRequirements(),
                'tool'     => $requirements->getToolRequirements(),
                'plugin'   => $requirements->getPluginRequirements(),
                'composer' => $requirements->getComposerRequirements(),
            ] as $key => $list
        ) {
            if ([] !== $encoded = $this->encodeRequirements($list)) {
                $output->{$key} = $encoded;
            }
        }

        return $output;
    }

    protected function encodeToolRequirements(ToolRequirements $requirements): stdClass
    {
        $output = new stdClass();
        foreach (
            [
                'php'      => $requirements->getPhpRequirements(),
                'composer' => $requirements->getComposerRequirements(),
            ] as $key => $list
        ) {
            if ([] !== $encoded = $this->encodeRequirements($list)) {
                $output->{$key} = $encoded;
            }
        }

        return $output;
    }

    protected function encodeRequirements(VersionRequirementList $requirementList): array
    {
        $requirements = [];
        foreach ($requirementList->getIterator() as $requirement) {
            $requirements[$requirement->getName()] = $requirement->getConstraint();
        }

        return $requirements;
    }

    /**
     * @return null|string[]
     *
     * @psalm-return array{type: string, value: string}|null
     */
    protected function encodeHash(?AbstractHash $hash): ?array
    {
        if (null === $hash) {
            return null;
        }
        return [
            'type'  => $hash->getType(),
            'value' => $hash->getValue(),
        ];
    }
}
