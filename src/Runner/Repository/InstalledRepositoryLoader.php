<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\RepositoryDefinition\FileGetContentsJsonFileLoader;
use Phpcq\RepositoryDefinition\JsonFileLoaderInterface;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirement;

/**
 * @psalm-type TRepositoryCheckSum = array{
 *   type: string,
 *   value: string,
 * }
 * @psalm-type TRepositoryIncludeList = list<array{
 *   url: string,
 *   checksum: TRepositoryCheckSum
 * }>
 * @psalm-type TRepositoryToolRequirements = array{
 *   php?: array<string, string>,
 *   composer?: array<string, string>,
 * }
 * @psalm-type TInstalledToolVersion = array{
 *   version: string,
 *   url: string,
 *   requirements: TRepositoryToolRequirements,
 *   checksum?: TRepositoryCheckSum,
 *   signature?: string,
 * }
 * @psalm-type TRepositoryPluginRequirements = array{
 *   php?: array<string, string>,
 *   tool?: array<string, string>,
 *   plugin?: array<string, string>,
 *   composer?: array<string, string>,
 * }
 * @psalm-type TInstalledPluginVersion = array{
 *   type: 'php-file'|'php-inline',
 *   version: string,
 *   api-version: string,
 *   requirements?: TRepositoryPluginRequirements,
 *   url: string,
 *   code?: string,
 *   checksum?: TRepositoryCheckSum,
 *   signature?: string,
 *   tools: array<string,TInstalledToolVersion>
 * }
 * @psalm-type TRepositoryInclude = array{
 *  url: string,
 *  checksum: TRepositoryCheckSum
 * }
 * @psalm-type TInstalledRepository = array{
 *  tools: array<string, TInstalledToolVersion>,
 *  plugins: array<string, TInstalledPluginVersion>,
 * }
 */
final class InstalledRepositoryLoader
{
    /**
     * @var JsonFileLoaderInterface
     */
    private $jsonFileLoader;

    /**
     * @param JsonFileLoaderInterface|null $jsonFileLoader
     */
    public function __construct(?JsonFileLoaderInterface $jsonFileLoader = null)
    {
        $this->jsonFileLoader = $jsonFileLoader ?: new FileGetContentsJsonFileLoader();
    }

    public function loadFile(string $filePath): InstalledRepository
    {
        /** @psalm-var TInstalledRepository $installed */
        $installed = $this->jsonFileLoader->load($filePath);

        return $this->createRepository($installed);
    }

    /** @psalm-param TInstalledRepository $installed */
    private function createRepository(array $installed): InstalledRepository
    {
        $repository = new InstalledRepository();

        foreach ($installed['plugins'] as $name => $config) {
            $repository->addPlugin($this->createInstalledPlugin($name, $config));
        }
        foreach ($installed['tools'] as $name => $config) {
            $repository->addToolVersion($this->createToolVersion($name, $config));
        }

        return $repository;
    }

    /** @psalm-param TInstalledPluginVersion $information */
    private function createInstalledPlugin(string $name, array $information): InstalledPlugin
    {
        $version = new PhpFilePluginVersion(
            $name,
            $information['version'],
            $information['api-version'],
            $this->loadPluginRequirements($information['requirements'] ?? []),
            $information['url'],
            $information['signature'] ?? null,
            $this->loadPluginHash($information['checksum'] ?? null)
        );

        $tools = [];
        foreach ($information['tools'] as $toolName => $toolConfig) {
            $tools[$toolName] = $this->createToolVersion($toolName, $toolConfig);
        }

        return new InstalledPlugin($version, $tools);
    }

    /** @psalm-param TInstalledToolVersion $information */
    private function createToolVersion(string $name, array $information): ToolVersionInterface
    {
        return new ToolVersion(
            $name,
            $information['version'],
            $information['url'],
            $this->loadToolRequirements($information['requirements']),
            $this->loadToolHash($information['checksum'] ?? null),
            $information['signature'] ?? null,
        );
    }

    /**
     * @psalm-param TRepositoryCheckSum|null $hash
     */
    private function loadPluginHash(?array $hash): ?PluginHash
    {
        if (null === $hash) {
            return null;
        }

        return PluginHash::create($hash['type'], $hash['value']);
    }

    /** @psalm-param TRepositoryPluginRequirements|null $requirements */
    private function loadPluginRequirements(?array $requirements): PluginRequirements
    {
        $result = new PluginRequirements();
        if (empty($requirements)) {
            return $result;
        }

        foreach (
            [
                'php'      => $result->getPhpRequirements(),
                'tool'     => $result->getToolRequirements(),
                'plugin'   => $result->getPluginRequirements(),
                'composer' => $result->getComposerRequirements(),
            ] as $key => $list
        ) {
            foreach ($requirements[$key] ?? [] as $name => $version) {
                $list->add(new VersionRequirement($name, $version));
            }
        }

        return $result;
    }


    /** @psalm-param TRepositoryToolRequirements|null $requirements */
    private function loadToolRequirements(?array $requirements): ToolRequirements
    {
        $result = new ToolRequirements();
        if (empty($requirements)) {
            return $result;
        }

        foreach (
            [
                'php'      => $result->getPhpRequirements(),
                'composer' => $result->getComposerRequirements(),
            ] as $key => $list
        ) {
            foreach ($requirements[$key] ?? [] as $name => $version) {
                $list->add(new VersionRequirement($name, $version));
            }
        }

        return $result;
    }

    /**
     * @psalm-param TRepositoryCheckSum|null $hash
     */
    private function loadToolHash(?array $hash): ?ToolHash
    {
        if (null === $hash) {
            return null;
        }

        return ToolHash::create($hash['type'], $hash['value']);
    }
}
