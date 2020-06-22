<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\PluginApi\Version10\Configuration\Builder\StringOptionBuilderInterface;

final class StringOptionBuilder extends AbstractOptionBuilder implements StringOptionBuilderInterface
{
    public function withDefaultValue(string $defaultValue) : StringOptionBuilderInterface
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }
}
