<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\RepositoryDefinition\Exception\RuntimeException;
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

use function array_map;
use function dirname;
use function explode;
use function filter_var;
use function implode;
use function is_file;
use function parse_url;
use function str_replace;

use const FILTER_VALIDATE_URL;
use const PHP_URL_PATH;

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
 *   type: 'php-file',
 *   version: string,
 *   api-version: string,
 *   requirements?: TRepositoryPluginRequirements,
 *   url: string,
 *   checksum: TRepositoryCheckSum,
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

    /** @var bool */
    private $failOnError;

    /**
     * @param JsonFileLoaderInterface|null $jsonFileLoader
     */
    public function __construct(?JsonFileLoaderInterface $jsonFileLoader = null, bool $failOnError = true)
    {
        $this->jsonFileLoader = $jsonFileLoader ?: new FileGetContentsJsonFileLoader();
        $this->failOnError    = $failOnError;
    }

    public function loadFile(string $filePath): InstalledRepository
    {
        $baseDir   = dirname($filePath);
        /** @psalm-var TInstalledRepository $installed */
        $installed = $this->jsonFileLoader->load($this->validateUrlOrFile($filePath, $baseDir));

        return $this->createRepository($installed, $baseDir);
    }

    /** @psalm-param TInstalledRepository $installed */
    private function createRepository(array $installed, string $baseDir): InstalledRepository
    {
        $repository = new InstalledRepository();

        foreach ($installed['plugins'] as $name => $config) {
            try {
                $repository->addPlugin($this->createInstalledPlugin($name, $config, $baseDir));
            } catch (RuntimeException $exception) {
                // FIXME: throw a different exception here?
                if ($this->failOnError) {
                    throw $exception;
                }
            }
        }
        foreach ($installed['tools'] as $name => $config) {
            try {
                $repository->addToolVersion($this->createToolVersion($name, $config, $baseDir));
            } catch (RuntimeException $exception) {
                // FIXME: throw a different exception here?
                if ($this->failOnError) {
                    throw $exception;
                }
            }
        }

        return $repository;
    }

    /** @psalm-param TInstalledPluginVersion $information */
    private function createInstalledPlugin(string $name, array $information, string $baseDir): InstalledPlugin
    {
        $version = new PhpFilePluginVersion(
            $name,
            $information['version'],
            $information['api-version'],
            $this->loadPluginRequirements($information['requirements'] ?? []),
            $this->validateUrlOrFile($information['url'], $baseDir),
            isset($information['signature'])
                ? $this->validateUrlOrFile($information['signature'], $baseDir)
                : null,
            $this->loadPluginHash($information['checksum'])
        );

        $tools = [];
        foreach ($information['tools'] as $toolName => $toolConfig) {
            try {
                $tools[] = $this->createToolVersion($toolName, $toolConfig, $baseDir);
            } catch (RuntimeException $exception) {
                // FIXME: throw a different exception here?
                if ($this->failOnError) {
                    throw $exception;
                }
            }
        }

        return new InstalledPlugin($version, $tools);
    }

    /** @psalm-param TInstalledToolVersion $information */
    private function createToolVersion(string $name, array $information, string $baseDir): ToolVersionInterface
    {
        return new ToolVersion(
            $name,
            $information['version'],
            $this->validateUrlOrFile($information['url'], $baseDir),
            $this->loadToolRequirements($information['requirements']),
            $this->loadToolHash($information['checksum'] ?? null),
            isset($information['signature'])
                ? $this->validateUrlOrFile($information['signature'], $baseDir)
                : null,
        );
    }

    /**
     * @psalm-param TRepositoryCheckSum $hash
     */
    private function loadPluginHash(array $hash): PluginHash
    {
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

    private function validateUrlOrFile(string $url, string $baseDir): string
    {
        // Local absolute path?
        if (is_file($url)) {
            return $url;
        }
        // Local relative path?
        if ('' !== $baseDir && is_file($baseDir . '/' . $url)) {
            return $baseDir . '/' . $url;
        }
        // Perform URL check.
        $path        = parse_url($url, PHP_URL_PATH);
        $encodedPath = array_map('urlencode', explode('/', $path));
        $newUrl      = str_replace($path, implode('/', $encodedPath), $url);
        if (filter_var($newUrl, FILTER_VALIDATE_URL)) {
            return $newUrl;
        }
        $newUrl = $baseDir . '/' . $newUrl;
        if (filter_var($newUrl, FILTER_VALIDATE_URL)) {
            return $newUrl;
        }

        // Did not understand.
        throw new RuntimeException('Invalid URI passed: ' . $url);
    }
}
