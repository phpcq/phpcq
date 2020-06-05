<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use IteratorAggregate;
use Phpcq\Exception\ToolNotFoundException;
use Phpcq\Platform\PlatformRequirementCheckerInterface;
use Traversable;

/**
 * Represents a JSON contained repository.
 */
class Repository implements IteratorAggregate, RepositoryInterface
{
    use RepositoryHasToolTrait;

    /**
     * @var ToolInformationInterface[][]
     *
     * @psalm-var array<string,list<ToolInformationInterface>>
     */
    private $tools = [];

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
     */
    public function __construct(?PlatformRequirementCheckerInterface $requirementChecker = null)
    {
        $this->requirementChecker = $requirementChecker;
        $this->parser = new VersionParser();
    }

    public function addVersion(ToolInformationInterface $toolVersion): void
    {
        $name = $toolVersion->getName();
        if (!isset($this->tools[$name])) {
            $this->tools[$name] = [];
        }
        $this->tools[$name][] = $toolVersion;
    }

    public function getTool(string $name, string $versionConstraint): ToolInformationInterface
    {
        // No tool specified, exit out.
        if (isset($this->tools[$name])) {
            $candidates = $this->findMatchingVersions($name, $versionConstraint);
            if (count($candidates) > 0) {
                return array_shift($candidates);
            }
        }

        throw new ToolNotFoundException($name, $versionConstraint);
    }

    /**
     * @return Traversable<int, ToolInformationInterface>
     *
     * @psalm-return \Generator<int, ToolInformationInterface, mixed, void>
     */
    public function getIterator()
    {
        foreach ($this->tools as $tool) {
            foreach ($tool as $version) {
                yield $version;
            }
        }
    }

    /**
     * @return ToolInformationInterface[]
     *
     * @psalm-return array<string, ToolInformationInterface>
     */
    private function findMatchingVersions(string $name, string $versionConstraint): array
    {
        $constraint = $this->parser->parseConstraints($versionConstraint);
        $results    = [];
        foreach ($this->tools[$name] as $versionHunk) {
            $version = $versionHunk->getVersion();
            if (!$constraint->matches(new Constraint('=', $this->parser->normalize($version)))) {
                continue;
            }

            if (!$this->matchesPlatformRequirements($versionHunk)) {
                continue;
            }

            $results[$version] = $versionHunk;
        }

        krsort($results);

        return $results;
    }

    private function matchesPlatformRequirements(ToolInformationInterface $versionHunk): bool
    {
        if (null === $this->requirementChecker) {
            return true;
        }
        foreach ($versionHunk->getPlatformRequirements() as $requirement => $constraints) {
            if (!$this->requirementChecker->isFulfilled($requirement, $constraints)) {
                return false;
            }
        }

        return true;
    }
}
