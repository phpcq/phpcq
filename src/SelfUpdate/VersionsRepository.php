<?php

declare(strict_types=1);

namespace Phpcq\Runner\SelfUpdate;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use Generator;
use Phpcq\RepositoryDefinition\VersionRequirementList;
use Phpcq\Runner\Exception\SelfUpdateVersionNotFound;
use Phpcq\Runner\Platform\PlatformRequirementCheckerInterface;

use function iterator_to_array;
use function krsort;

final class VersionsRepository
{
    private PlatformRequirementCheckerInterface $requirementChecker;

    /** @var list<Version> */
    private array $versions = [];

    private VersionParser $parser;

    public function __construct(PlatformRequirementCheckerInterface $requirementChecker)
    {
        $this->requirementChecker = $requirementChecker;
        $this->parser = new VersionParser();
    }

    public function addVersion(Version $version): self
    {
        $this->versions[] = $version;

        return $this;
    }

    public function findMatchingVersion(?string $versionConstraint = null, bool $signed = true): Version
    {
        $constraint = $versionConstraint ? $this->parser->parseConstraints($versionConstraint) : null;
        $results    = [];

        foreach ($this->findInstallableVersions() as $normalized => $version) {
            if ($constraint !== null && !$constraint->matches(new Constraint('=', $normalized))) {
                continue;
            }

            if ($signed && $version->getSignatureFile() === null) {
                continue;
            }

            $results[$normalized] = $version;
        }

        krsort($results, SORT_NATURAL);

        if (count($results) > 0) {
            return array_shift($results);
        }

        throw new SelfUpdateVersionNotFound($versionConstraint ?? '*');
    }

    /** @return Generator<string, Version> */
    private function findInstallableVersions(): Generator
    {
        foreach ($this->versions as $version) {
            if (!$this->matchesPlatformRequirements($version->getRequirements())) {
                continue;
            }

            $normalized = $this->parser->normalize($version->getVersion());

            yield $normalized => $version;
        }
    }

    private function matchesPlatformRequirements(VersionRequirementList $requirements): bool
    {
        foreach ($requirements as $requirement) {
            if (!$this->requirementChecker->isFulfilled($requirement->getName(), $requirement->getConstraint())) {
                return false;
            }
        }

        return true;
    }
}
