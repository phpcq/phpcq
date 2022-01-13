<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition;

abstract class AbstractDefinition
{
    /** @var string */
    private $name;

    /** @var string */
    private $description;

    public function __construct(string $name, string $description)
    {
        $this->name        = $name;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
