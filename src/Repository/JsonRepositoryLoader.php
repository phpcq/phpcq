<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\Platform\PlatformRequirementCheckerInterface;

use function array_keys;
use function dirname;
use function is_array;

/**
 * Load a json file.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-type TToolHash = array{
 *   type: 'sha-1'|'sha-256'|'sha-384'|'sha-512',
 *   value: string
 * }
 * @psalm-type TBootstrapInline = array{
 *    type: 'inline',
 *    code: string,
 *    plugin-version: string
 * }
 * @psalm-type TBootstrapFile = array{
 *    type: 'file',
 *    url: string,
 *    plugin-version: string
 * }
 * @psalm-type TBootstrap = TBootstrapInline|TBootstrapFile
 * @psalm-type TToolConfigJson = array{
 *    version: string,
 *    phar-url: string,
 *    bootstrap: string|TBootstrap,
 *    requirements: array<string,string>,
 *    hash?: TToolHash,
 *    signature?: string
 * }
 * @psalm-type TRepositoryInclude = array{url:string, checksum:TToolHash|null}
 * @psalm-type TJsonRepository = array{
 *   bootstraps?: array<string, TBootstrap>,
 *   phars: array<string,TRepositoryInclude|list<TToolConfigJson>>,
 * }
 */
class JsonRepositoryLoader
{
    /**
     * @var FileDownloader
     */
    private $downloader;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var bool
     */
    private $bypassCache;

    /**
     * @var PlatformRequirementCheckerInterface|null
     */
    private $requirementChecker;

    /**
     * Create a new instance.
     *
     * @param PlatformRequirementCheckerInterface|null $requirementChecker
     * @param FileDownloader                           $downloader
     * @param bool                                     $bypassCache
     */
    public function __construct(
        ?PlatformRequirementCheckerInterface $requirementChecker,
        FileDownloader $downloader,
        bool $bypassCache = false
    ) {
        $this->requirementChecker = $requirementChecker;
        $this->downloader = $downloader;
        $this->bypassCache = $bypassCache;
    }

    /** @psalm-param ?TToolHash $hash */
    public function loadFile(string $filePath, ?array $hash = null, ?string $baseDir = null): RepositoryInterface
    {
        $this->repository = new Repository($this->requirementChecker);
        $this->includeFile($filePath, $hash, $baseDir);

        return $this->repository;
    }

    /** @psalm-param ?TToolHash $hash */
    private function includeFile(string $filePath, ?array $hash = null, ?string $baseDir = null): void
    {
        $baseDir          = $baseDir ?? dirname($filePath);
        $data             = $this->downloader->downloadJsonFile($filePath, $baseDir, $this->bypassCache, $hash);
        $bootstrapLookup  = $data['bootstraps'] ?? [];
        foreach ($data['phars'] as $toolName => $versions) {
            // Include? - load it!
            if (['url', 'checksum'] === array_keys($versions)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument
                 * @psalm-suppress ArgumentTypeCoercion
                 */
                $this->includeFile(
                    $versions['url'],
                    $versions['checksum'],
                    $baseDir
                );
                continue;
            }

            /** @psalm-suppress InvalidArgument */
            $this->handleVersionList($toolName, $versions, $bootstrapLookup, $baseDir);
        }
    }

    /**
     * @psalm-param list<TToolConfigJson> $versionList
     * @psalm-param array<string, TBootstrap> $bootstrapLookup
     */
    private function handleVersionList(
        string $toolName,
        array $versionList,
        array $bootstrapLookup,
        string $baseDir
    ): void {
        foreach ($versionList as $version) {
            if (is_string($bootstrap = $version['bootstrap'])) {
                if (!isset($bootstrapLookup[$bootstrap])) {
                    throw new RuntimeException('Bootstrap not in lookup map: ' . $bootstrap);
                }
                $version['bootstrap'] = $bootstrapLookup[$bootstrap];
            }
            $this->repository->addVersion(new ToolInformation(
                $toolName,
                $version['version'],
                $version['phar-url'],
                $version['requirements'],
                $this->makeBootstrap($version['bootstrap'], $bootstrapLookup, $baseDir),
                isset($version['hash']) ? new ToolHash($version['hash']['type'], $version['hash']['value']) : null,
                $version['signature'] ?? null
            ));
        }
    }

    /**
     * @param string|array $bootstrap
     * @param-param string|TBootstrap $bootstrap
     * @psalm-param array<string, TBootstrap> $bootstrapLookup
     */
    private function makeBootstrap($bootstrap, array $bootstrapLookup, string $baseDir): BootstrapInterface
    {
        if (is_string($bootstrap)) {
            if (!isset($bootstrapLookup[$bootstrap])) {
                throw new RuntimeException('Bootstrap not in lookup map: ' . $bootstrap);
            }
            $bootstrap = $bootstrapLookup[$bootstrap];
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if (!is_array($bootstrap)) {
            throw new RuntimeException('Invalid bootstrap definition: ' . json_encode($bootstrap));
        }

        switch ($bootstrap['type']) {
            case 'inline':
                // Static bootstrapper.
                /** @psalm-var TBootstrapInline $bootstrap */
                return new InlineBootstrap(
                    $bootstrap['plugin-version'],
                    $bootstrap['code'],
                    isset($bootstrap['hash'])
                        ? new BootstrapHash($bootstrap['hash']['type'], $bootstrap['hash']['value'])
                        : null,
                );
            case 'file':
                /** @psalm-var TBootstrapFile $bootstrap */
                return new RemoteBootstrap(
                    $bootstrap['plugin-version'],
                    $bootstrap['url'],
                    isset($bootstrap['hash'])
                        ? new BootstrapHash($bootstrap['hash']['type'], $bootstrap['hash']['value'])
                        : null,
                    $this->downloader,
                    $baseDir
                );
        }
        throw new RuntimeException('Invalid bootstrap definition: ' . json_encode($bootstrap));
    }
}
