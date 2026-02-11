<?php

declare(strict_types=1);

namespace Phpcq\Runner\Platform;

use Closure;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;

class PlatformRequirementChecker implements PlatformRequirementCheckerInterface
{
    /**
     * @var callable(string, string): bool
     */
    private $callback;

    public static function create(?PlatformInformationInterface $platformInformation = null): self
    {
        $platformInformation ??= PlatformInformation::createFromCurrentPlatform();

        $parser = new VersionParser();
        return new self(function (string $name, string $constraint) use ($platformInformation, $parser): bool {
            $installedVersion = $platformInformation->getInstalledVersion($name);

            // Requirement is not available
            if (null === $installedVersion) {
                return false;
            }
            $constraints = $parser->parseConstraints($constraint);

            return $constraints->matches(new Constraint('=', $parser->normalize($installedVersion)));
        });
    }

    public static function createAlwaysFulfilling(): self
    {
        return new self(fn(): bool => true);
    }

    /** @param callable(string, string):bool $callback */
    private function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    #[\Override]
    public function isFulfilled(string $name, string $constraint): bool
    {
        return ($this->callback)($name, $constraint);
    }
}
