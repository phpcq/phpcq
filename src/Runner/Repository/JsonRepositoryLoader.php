<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Phpcq\Platform\PlatformRequirementCheckerInterface;
use Phpcq\RepositoryDefinition\JsonFileLoaderInterface;
use Phpcq\RepositoryDefinition\RepositoryLoader;

/**
 * Load a json file.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-type THash = array{
 *   type: 'sha-1'|'sha-256'|'sha-384'|'sha-512',
 *   value: string
 * }
 * @psalm-type TBootstrapInline = array{
 *    type: 'inline',
 *    code: string,
 *    plugin-version: string,
 *    hash: ?THash
 * }
 * @psalm-type TBootstrapFile = array{
 *    type: 'file',
 *    url: string,
 *    plugin-version: string,
 *    hash: ?THash
 * }
 * @psalm-type TBootstrap = TBootstrapInline|TBootstrapFile
 * @psalm-type TToolConfigJson = array{
 *    version: string,
 *    phar-url: string,
 *    bootstrap: string|TBootstrap,
 *    requirements: array<string,string>,
 *    hash?: THash,
 *    signature?: string
 * }
 * @psalm-type TRepositoryInclude = array{url:string, checksum:THash|null}
 * @psalm-type TJsonRepository = array{
 *   bootstraps?: array<string, TBootstrap>,
 *   phars: array<string,TRepositoryInclude|list<TToolConfigJson>>,
 * }
 */
class JsonRepositoryLoader
{
    /**
     * @var JsonFileLoaderInterface|null
     */
    private $jsonFileLoader;

    /**
     * @var PlatformRequirementCheckerInterface|null
     */
    private $requirementChecker;

    /**
     * Create a new instance.
     *
     * @param PlatformRequirementCheckerInterface|null $requirementChecker
     * @param JsonFileLoaderInterface|null             $jsonFileLoader
     */
    public function __construct(
        ?PlatformRequirementCheckerInterface $requirementChecker = null,
        ?JsonFileLoaderInterface $jsonFileLoader = null
    ) {
        $this->requirementChecker = $requirementChecker;
        $this->jsonFileLoader     = $jsonFileLoader;
    }

    /** @psalm-param ?THash $hash */
    public function loadFile(string $filePath, ?array $hash = null): RepositoryInterface
    {
        return new Repository(
            $this->requirementChecker,
            RepositoryLoader::loadRepository($filePath, $hash, $this->jsonFileLoader),
        );
    }
}
