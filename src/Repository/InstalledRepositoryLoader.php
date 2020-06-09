<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\Platform\PlatformRequirementCheckerInterface;

/**
 * Load a installed.json file.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-type TBootstrapFile = array{
 *    type: 'file',
 *    url: string,
 *    plugin-version: string
 * }
 * @psalm-type TToolConfigInstalled = array{
 *    version: string,
 *    phar-url: string,
 *    bootstrap: TBootstrapFile,
 *    requirements: array<string,string>,
 *    signature?: string
 * }
 * @psalm-type TInstalledRepository = array{
 *   phars: array<string,list<TToolConfigInstalled>>
*  }
 */
class InstalledRepositoryLoader
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var PlatformRequirementCheckerInterface|null
     */
    private $requirementChecker;

    /**
     * Create a new instance.
     *
     * @param PlatformRequirementCheckerInterface|null $requirementChecker
     */
    public function __construct(?PlatformRequirementCheckerInterface $requirementChecker)
    {
        $this->requirementChecker = $requirementChecker;
    }

    public function loadFile(string $filePath, ?string $baseDir = null): RepositoryInterface
    {
        $this->repository = new Repository($this->requirementChecker);
        $fileName         = basename($filePath);
        $baseDir          = $baseDir ?? dirname($filePath);
        $data             = $this->readFile($fileName, $baseDir);
        /** @psalm-var TInstalledRepository $data */
        foreach ($data['phars'] as $toolName => $versions) {
            $this->handleVersionList($toolName, $versions, $baseDir);
        }

        return $this->repository;
    }

    /** @psalm-return TInstalledRepository */
    private function readFile(string $fileName, string $baseDir): array
    {
        if (!is_file($filePath = $fileName)) {
            if (!is_file($filePath = $baseDir . '/' . $fileName)) {
                throw new RuntimeException('File not found: ' . $filePath);
            }
        }

        /**
         * @var null|array $data
         * @psalm-var ?TInstalledRepository $data
         */
        $data = json_decode(file_get_contents($filePath), true);
        if (null === $data) {
            throw new RuntimeException('Invalid repository ' . $fileName);
        }

        return $data;
    }

    /** @psalm-param list<TToolConfigInstalled> $versionList */
    private function handleVersionList(string $toolName, array $versionList, string $baseDir): void
    {
        foreach ($versionList as $version) {
            $this->repository->addVersion(new ToolInformation(
                $toolName,
                $version['version'],
                $version['phar-url'],
                $version['requirements'],
                $this->makeBootstrap($version['bootstrap'], $baseDir),
                null,
                $version['signature'] ?? null
            ));
        }
    }

    /** @psalm-param TBootstrapFile $bootstrap */
    private function makeBootstrap(array $bootstrap, string $baseDir): BootstrapInterface
    {
        if (($bootstrap['type'] ?? null) !== 'file') {
            throw new RuntimeException('Invalid bootstrap definition: ' . json_encode($bootstrap));
        }

        if (!is_file($filePath = $baseDir . '/' . $bootstrap['url'])) {
            throw new RuntimeException('Bootstrap file not found: ' . $filePath);
        }

        return new InstalledBootstrap($bootstrap['plugin-version'], $filePath);
    }
}
