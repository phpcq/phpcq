<?php

declare(strict_types=1);

namespace Phpcq\Runner\Repository;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use Generator;
use Phpcq\Runner\Exception\PluginVersionNotFoundException;
use Phpcq\Runner\Exception\ToolVersionNotFoundException;
use Phpcq\Runner\Platform\PlatformRequirementCheckerInterface;
use Phpcq\RepositoryDefinition\Plugin\Plugin;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Repository as DecoratedRepository;
use Phpcq\RepositoryDefinition\RepositoryInterface as DefinitionRepositoryInterface;
use Phpcq\RepositoryDefinition\Tool\Tool;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirementList;
use Traversable;

use function array_shift;
use function count;
use function krsort;

use const SORT_NATURAL;

/**
 * Represents a JSON contained repository.
 */
class Repository implements RepositoryInterface
{
    use RepositoryHasToolVersionTrait;
    use RepositoryHasPluginVersionTrait;

    /**
     * @var DefinitionRepositoryInterface
     */
    private $repository;

    /**
     * @var VersionParser
     */
    private $parser;

    /**
     * @var PlatformRequirementCheckerInterface|null
     */
    private $requirementChecker;

    /**
     * Repository constructor.
     *
     * @param PlatformRequirementCheckerInterface|null $requirementChecker
     * @param DefinitionRepositoryInterface|null       $repository
     */
    public function __construct(
        ?PlatformRequirementCheckerInterface $requirementChecker = null,
        ?DefinitionRepositoryInterface $repository = null
    ) {
        $this->repository         = $repository ?: new DecoratedRepository();
        $this->requirementChecker = $requirementChecker;
        $this->parser             = new VersionParser();
    }

    public function addPluginVersion(PluginVersionInterface $pluginVersion): void
    {
        if (! $this->repository->hasPlugin($pluginVersion->getName())) {
            $this->repository->addPlugin(new Plugin($pluginVersion->getName()));
        }

        $this->repository->getPlugin($pluginVersion->getName())->addVersion($pluginVersion);
    }

    public function getPluginVersion(string $name, string $versionConstraint): PluginVersionInterface
    {
        if ($this->repository->hasPlugin($name)) {
            $candidates = $this->findMatchingPluginVersions($name, $versionConstraint);
            if (count($candidates) > 0) {
                return array_shift($candidates);
            }
        }

        throw new PluginVersionNotFoundException($name, $versionConstraint);
    }

    public function iteratePluginVersions(): Generator
    {
        foreach ($this->repository->iteratePlugins() as $plugin) {
            foreach ($plugin as $pluginVersion) {
                yield $pluginVersion;
            }
        }
    }

    public function addToolVersion(ToolVersionInterface $toolVersion): void
    {
        if (! $this->repository->hasTool($toolVersion->getName())) {
            $this->repository->addTool(new Tool($toolVersion->getName()));
        }

        $this->repository->getTool($toolVersion->getName())->addVersion($toolVersion);
    }

    public function getToolVersion(string $name, string $versionConstraint): ToolVersionInterface
    {
        // No tool specified, exit out.
        if ($this->repository->hasTool($name)) {
            $candidates = $this->findMatchingToolVersions($name, $versionConstraint);
            if (count($candidates) > 0) {
                return array_shift($candidates);
            }
        }

        throw new ToolVersionNotFoundException($name, $versionConstraint);
    }

    /**
     * @return Traversable<int, ToolVersionInterface>
     *
     * @psalm-return \Generator<int, ToolVersionInterface, mixed, void>
     */
    public function iterateToolVersions(): Generator
    {
        foreach ($this->repository->iterateTools() as $tool) {
            foreach ($tool as $version) {
                yield $version;
            }
        }
    }

    /**
     * @return PluginVersionInterface[]
     *
     * @psalm-return array<string, PluginVersionInterface>
     */
    private function findMatchingPluginVersions(string $name, string $versionConstraint): array
    {
        $constraint = $this->parser->parseConstraints($versionConstraint);
        $results    = [];

        /** @var PluginVersionInterface $versionHunk */
        foreach ($this->repository->getPlugin($name) as $versionHunk) {
            $version = $versionHunk->getVersion();
            if (!$constraint->matches(new Constraint('=', $this->parser->normalize($version)))) {
                continue;
            }

            if (!$this->matchesPlatformRequirements($versionHunk->getRequirements()->getPhpRequirements())) {
                continue;
            }

            $results[$version] = $versionHunk;
        }

        krsort($results);

        return $results;
    }

    /**
     * @return ToolVersionInterface[]
     *
     * @psalm-return array<string, ToolVersionInterface>
     */
    private function findMatchingToolVersions(string $name, string $versionConstraint): array
    {
        $constraint = $this->parser->parseConstraints($versionConstraint);
        $results    = [];

        /** @var ToolVersionInterface $versionHunk */
        foreach ($this->repository->getTool($name) as $versionHunk) {
            $version = $versionHunk->getVersion();
            if (!$constraint->matches(new Constraint('=', $this->parser->normalize($version)))) {
                continue;
            }

            if (!$this->matchesPlatformRequirements($versionHunk->getRequirements()->getPhpRequirements())) {
                continue;
            }

            $results[$version] = $versionHunk;
        }

        krsort($results, SORT_NATURAL);

        return $results;
    }

    private function matchesPlatformRequirements(VersionRequirementList $requirements): bool
    {
        if (null === $this->requirementChecker) {
            return true;
        }
        foreach ($requirements as $requirement) {
            if (!$this->requirementChecker->isFulfilled($requirement->getName(), $requirement->getConstraint())) {
                return false;
            }
        }

        return true;
    }
}
