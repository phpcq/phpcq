<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Exception\InvalidArgumentException;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;

class PluginConfigurationBuilder extends AbstractOptionsBuilder implements PluginConfigurationBuilderInterface
{
    public function supportDirectories(): PluginConfigurationBuilderInterface
    {
        if (isset($this->options['directories'])) {
            return $this;
        }

        $builder = new ListOptionBuilder(
            $this,
            'directories',
            'List of directory paths which the plugin should process'
        );
        $builder->ofStringItems();
        // TODO: Shall we validate the directories values

        $this->options['directories'] = $builder;

        return $this;
    }

    protected function describeOption(string $name, OptionBuilderInterface $builder): void
    {
        if ('directories' === $name) {
            throw new InvalidArgumentException('Directories is a reserved configuration key');
        }

        parent::describeOption($name, $builder);
    }
}
