<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Exception\RuntimeException;

trait TypeTrait
{
    private ?string $type = null;

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
