<?php

declare(strict_types=1);

namespace Phpcq\Runner\SelfUpdate;

use Phpcq\RepositoryDefinition\VersionRequirement;
use Phpcq\RepositoryDefinition\VersionRequirementList;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Platform\PlatformRequirementCheckerInterface;

/**
 * @psalm-type TVersion = array{
 *     version: string,
 *     requirements: array<string, string>,
 *     phar: non-empty-string,
 *     signature: non-empty-string|null,
 * }
 * @psalm-type TVersionRepository = array{
 *     updated: non-empty-string,
 *     versions: list<TVersion>,
 * }
 */
final class VersionsRepositoryLoader
{
    public function __construct(
        private readonly PlatformRequirementCheckerInterface $requirementChecker,
        private readonly DownloaderInterface $downloader
    ) {
    }

    public function load(string $file): VersionsRepository
    {
        /** @var TVersionRepository $json */
        $json         = $this->downloader->downloadJsonFile($file, '', true);
        $repository   = new VersionsRepository($this->requirementChecker);

        foreach ($json['versions'] as $version) {
            $requirements = new VersionRequirementList();

            foreach ($version['requirements'] ?? [] as $name => $constraint) {
                $requirements->add(new VersionRequirement($name, $constraint));
            }

            $repository->addVersion(
                new Version(
                    $version['version'],
                    $requirements,
                    $version['phar'],
                    $version['signature'] ?? null
                )
            );
        }

        return $repository;
    }
}
