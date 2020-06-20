<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

class RootOptionsBuilder extends AbstractArrayOptionBuilder
{
    public function __construct(string $name, string $description)
    {
        parent::__construct($name, $description);
    }
}
