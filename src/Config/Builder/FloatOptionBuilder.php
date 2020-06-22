<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\PluginApi\Version10\Configuration\Builder\FloatOptionBuilderInterface;

final class FloatOptionBuilder extends AbstractOptionBuilder implements FloatOptionBuilderInterface
{
    public function withDefaultValue(float $defaultValue) : FloatOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
