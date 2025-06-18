<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\Runner\Platform\PlatformRequirementCheckerInterface;
use Phpcq\RepositoryDefinition\JsonFileLoaderInterface;
use Phpcq\RepositoryDefinition\RepositoryLoader;

/**
 * Load a json file.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-type TRepositoryCheckSum = array{
 *   type: string,
 *   value: string,
 * }
 * @psalm-type THash = array{
 *   type: 'sha-1'|'sha-256'|'sha-384'|'sha-512',
 *   value: string
 * }
 * @psalm-type TBootstrapFile = array{
 *    type: 'file',
 *    url: string,
 *    plugin-version: string,
 *    hash: ?TRepositoryCheckSum
 * }
 * @psalm-type TBootstrap = TBootstrapFile
 * @psalm-type TToolConfigJson = array{
 *    version: string,
 *    phar-url: string,
 *    bootstrap: string|TBootstrap,
 *    requirements: array<string,string>,
 *    hash?: TRepositoryCheckSum,
 *    signature?: string
 * }
 * @psalm-type TRepositoryInclude = array{url:string, checksum:TRepositoryCheckSum|null}
 * @psalm-type TJsonRepository = array{
 *   bootstraps?: array<string, TBootstrap>,
 *   phars: array<string,TRepositoryInclude|list<TToolConfigJson>>,
 * }
 */
class JsonRepositoryLoader
{
    /**
     * Create a new instance.
     *
     * @param PlatformRequirementCheckerInterface|null $requirementChecker
     * @param JsonFileLoaderInterface|null             $jsonFileLoader
     */
    public function __construct(
        private readonly ?PlatformRequirementCheckerInterface $requirementChecker = null,
        private readonly ?JsonFileLoaderInterface $jsonFileLoader = null
    ) {
    }

    /** @param ?TRepositoryCheckSum $hash */
    public function loadFile(string $filePath, ?array $hash = null): RepositoryInterface
    {
        return new Repository(
            $this->requirementChecker,
            RepositoryLoader::loadRepository($filePath, $hash, $this->jsonFileLoader),
        );
    }
}
