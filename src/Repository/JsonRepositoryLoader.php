<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;
use Phpcq\Platform\PlatformInformationInterface;

/**
 * Load a json file.
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
     * @var PlatformInformationInterface
     */
    private $platformInformation;

    /**
     * Create a new instance.
     *
     * @param PlatformInformationInterface $platformInformation
     * @param FileDownloader $downloader
     * @param bool $bypassCache
     */
    public function __construct(PlatformInformationInterface $platformInformation, FileDownloader $downloader, bool $bypassCache = false)
    {
        $this->platformInformation = $platformInformation;
        $this->downloader = $downloader;
        $this->bypassCache = $bypassCache;
    }

    public function loadFile(string $filePath): RepositoryInterface
    {
        $this->repository = new Repository($this->platformInformation);
        $baseDir          = dirname($filePath);
        $data             = $this->downloader->downloadJsonFile($filePath, $baseDir, $this->bypassCache);
        $bootstrapLookup  = $data['bootstraps'] ?? [];
        foreach ($data['phars'] as $toolName => $versions) {
            switch (true) {
                case is_string($versions):
                    $this->handleVersionList(
                        $toolName,
                        $this->downloader->downloadJsonFile($versions, $baseDir, $this->bypassCache),
                        [],
                        $baseDir
                    );
                    break;
                case is_array($versions):
                    $this->handleVersionList($toolName, $versions, $bootstrapLookup, $baseDir);
                    break;
                default:
                    throw new RuntimeException('Invalid version list');
            }
        }

        return $this->repository;
    }

    private function handleVersionList(string $toolName, array $versionList, array $bootstrapLookup, string $baseDir)
    {
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
                $this->makeBootstrap($version['bootstrap'], $bootstrapLookup, $baseDir)
            ));
        }
    }

    private function makeBootstrap($bootstrap, array $bootstrapLookup, string $baseDir): BootstrapInterface
    {
        if (is_string($bootstrap)) {
            if (!isset($bootstrapLookup[$bootstrap])) {
                throw new RuntimeException('Bootstrap not in lookup map: ' . $bootstrap);
            }
            $bootstrap = $bootstrapLookup[$bootstrap];
        }
        if (!is_array($bootstrap)) {
            throw new RuntimeException('Invalid bootstrap definition: ' . json_encode($bootstrap));
        }

        switch ($bootstrap['type']) {
            case 'inline':
                // Static bootstrapper.
                return new InlineBootstrap($bootstrap['plugin-version'], $bootstrap['code']);
            case 'file':
                return new RemoteBootstrap($bootstrap['plugin-version'], $bootstrap['url'], $this->downloader, $baseDir);
        }
        throw new RuntimeException('Invalid bootstrap definition: ' . json_encode($bootstrap));
    }
}
