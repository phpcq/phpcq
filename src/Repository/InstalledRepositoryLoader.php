<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Phpcq\Exception\RuntimeException;
use Phpcq\Platform\PlatformRequirementCheckerInterface;

/**
 * Load a installed.json file.
 *
 * @psalm-suppress PropertyNotSetInConstructor
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
        foreach ($data['phars'] as $toolName => $versions) {
            if (!is_array($versions)) {
                throw new RuntimeException('Invalid version list');
            }

            /** @psalm-suppress InvalidArgument */
            $this->handleVersionList($toolName, $versions, $baseDir);
        }

        return $this->repository;
    }

    private function readFile(string $fileName, string $baseDir): array
    {
        if (!is_file($filePath = $fileName)) {
            if (!is_file($filePath = $baseDir . '/' . $fileName)) {
                throw new RuntimeException('File not found: ' . $filePath);
            }
        }

        return json_decode(file_get_contents($filePath), true);
    }

    private function handleVersionList(string $toolName, array $versionList, string $baseDir): void
    {
        foreach ($versionList as $version) {
            $this->repository->addVersion(new ToolInformation(
                $toolName,
                $version['version'],
                $version['phar-url'],
                $version['requirements'],
                $this->makeBootstrap($version['bootstrap'], $baseDir),
                isset($version['hash']) ? new ToolHash($version['hash']['type'], $version['hash']['value']) : null,
                $version['signature'] ?? null
            ));
        }
    }

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
