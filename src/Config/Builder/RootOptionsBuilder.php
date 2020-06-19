<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Configuration\Builder\NodeBuilderInterface;

class RootOptionsBuilder extends AbstractArrayOptionBuilder
{
    public function __construct(string $name, string $description)
    {
        parent::__construct($this, $name, $description);
    }

    public function end() : NodeBuilderInterface
    {
        throw new RuntimeException('Root option does not have a parent node');
    }
}