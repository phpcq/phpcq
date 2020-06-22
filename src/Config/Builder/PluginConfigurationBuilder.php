<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\Exception\InvalidArgumentException;
use Phpcq\PluginApi\Version10\Configuration\Builder\OptionBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;

class PluginConfigurationBuilder extends AbstractOptionsBuilder implements PluginConfigurationBuilderInterface
{
    /** @var bool */
    private $supportsDirectories = false;

    public function supportDirectories(): PluginConfigurationBuilderInterface
    {
        if ($this->supportsDirectories) {
            return $this;
        }

        $this->supportsDirectories = true;
        $builder = new StringListOptionBuilder(
            'directories',
            'List of directory paths which the plugin should process'
        );
        // TODO: Shall we validate the directories values

        $this->options['directories'] = $builder;

        return $this;
    }

    public function hasDirectoriesSupport(): bool
    {
        return $this->supportsDirectories;
    }

    protected function describeOption(string $name, OptionBuilderInterface $builder): void
    {
        if ('directories' === $name) {
            throw new InvalidArgumentException('Directories is a reserved configuration key');
        }

        parent::describeOption($name, $builder);
    }
}
