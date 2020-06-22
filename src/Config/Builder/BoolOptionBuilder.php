<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\PluginApi\Version10\Configuration\Builder\BoolOptionBuilderInterface;

final class BoolOptionBuilder extends AbstractOptionBuilder implements BoolOptionBuilderInterface
{
    public function withDefaultValue(bool $defaultValue) : BoolOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
