<?php

declare(strict_types=1);

namespace Phpcq\Runner\Plugin;

use Phpcq\RepositoryDefinition\Plugin\ApiVersionRange;

/**
 * The plugin API versions supported by this runner.
 */
final class ApiVersion
{
    public const MIN = '1.0.0';

    public const MAX = '1.1.0';

    public static function range(): ApiVersionRange
    {
        return new ApiVersionRange(self::MIN, self::MAX);
    }
}
