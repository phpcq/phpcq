<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Exception\RuntimeException;

trait TypeTrait
{
    /** @var string|null */
    private $type;

    private function declareType(string $requestedType): void
    {
        if (null !== $this->type) {
            throw new RuntimeException(
                sprintf('Not allowed to redeclare list type. Type "%s" already declared', $this->type)
            );
        }

        $this->type = $requestedType;
    }
}
