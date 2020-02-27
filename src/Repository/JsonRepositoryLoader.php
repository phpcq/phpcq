<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\FileDownloader;

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
     * Create a new instance.
     *
     * @param FileDownloader $downloader
     */
    public function __construct(FileDownloader $downloader)
    {
        $this->downloader = $downloader;
    }

    public function loadFile(string $filePath): RepositoryInterface
    {
        $this->repository = new Repository();
        $baseDir          = dirname($filePath);
        $data             = $this->downloader->downloadJsonFile($filePath, $baseDir);
        $bootstrapLookup  = $data['bootstraps'] ?? [];
        foreach ($data['phars'] as $toolName => $versions) {
            switch (true) {
                case is_string($versions):
                    $this->handleVersionList(
                        $toolName,
                        $this->downloader->downloadJsonFile($versions, $baseDir),
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
