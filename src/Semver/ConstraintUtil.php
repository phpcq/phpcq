<?php

declare(strict_types=1);

namespace Phpcq\Runner\Semver;

use Composer\Semver\VersionParser;

final class ConstraintUtil
{
    public static function matches(string $requirement, string $constraints): bool
    {
        $constraints = self::versionParser()->parseConstraints($constraints);
        $requirement = self::versionParser()->parseConstraints($requirement);

        return $constraints->matches($requirement);
    }

    private static function versionParser(): VersionParser
    {
        /** @var VersionParser|null $parser */
        static $parser = null;

        if ($parser === null) {
            $parser = new VersionParser();
        }

        return $parser;
    }
}
