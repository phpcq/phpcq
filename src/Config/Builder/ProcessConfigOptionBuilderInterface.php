<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;

interface ProcessConfigOptionBuilderInterface extends OptionBuilderInterface
{
    public function processConfig($raw);
}