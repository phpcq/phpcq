<?php

declare(strict_types=1);

namespace Phpcq\Runner\Platform;

interface PlatformRequirementCheckerInterface
{
    public function isFulfilled(string $name, string $constraint): bool;
}
