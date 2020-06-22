<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\PluginApi\Version10\Configuration\Builder\IntOptionBuilderInterface;

final class IntOptionBuilder extends AbstractOptionBuilder implements IntOptionBuilderInterface
{
    public function withDefaultValue(int $defaultValue) : IntOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
