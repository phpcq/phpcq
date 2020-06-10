<?php

declare(strict_types=1);

namespace Phpcq\Repository;

/**
 * @psalm-type TToolHash = array{
 *   type: 'sha-1'|'sha-256'|'sha-384'|'sha-512',
 *   value: string
 * }
 */
class ToolHash extends AbstractHash
{
}
