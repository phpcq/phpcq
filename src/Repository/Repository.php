<?php

declare(strict_types=1);

namespace Phpcq\Repository;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use IteratorAggregate;
use Phpcq\Exception\ToolNotFoundException;
use Traversable;

/**
 * Represents a JSON contained repository.
 */
class Repository implements RepositoryInterface, IteratorAggregate
{
    use RepositoryHasToolTrait;

    private $tools = [];

    /**
     * @var VersionParser
     */
    private $parser;

    public function addVersion(ToolInformationInterface $toolVersion)
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
     * @return ToolInformationInterface[]|Traversable
     */
    public function getIterator()
    {
        foreach ($this->tools as $tool) {
            foreach ($tool as $version) {
                yield $version;
            }
        }
    }

    private function findMatchingVersions(string $name, string $versionConstraint)
    {
        if (!$this->parser) {
            $this->parser = new VersionParser();
        }
        $constraint = $this->parser->parseConstraints($versionConstraint);
        $results    = [];
        foreach ($this->tools[$name] as $versionHunk) {
            $version = $versionHunk->getVersion();
            if ($constraint->matches(new Constraint('=', $this->parser->normalize($version)))) {
                $results[$version] = $versionHunk;
            }
        }

        krsort($results);

        return $results;
    }
}