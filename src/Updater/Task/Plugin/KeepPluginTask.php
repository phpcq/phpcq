<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Updater\UpdateContext;

use function assert;
use function sprintf;

final class KeepPluginTask extends AbstractPluginTask
{
    /** @var PluginVersionInterface */
    private $installedVersion;

    public function __construct(PluginVersionInterface $pluginVersion, PluginVersionInterface $installedVersion)
    {
        parent::__construct($pluginVersion);

        $this->installedVersion = $installedVersion;
    }

    #[\Override]
    public function getPurposeDescription(): string
    {
        return sprintf(
            'Will keep plugin %s in version %s',
            $this->pluginVersion->getName(),
            $this->pluginVersion->getVersion()
        );
    }

    #[\Override]
    public function getExecutionDescription(): string
    {
        return sprintf(
            'Keeping plugin %s in version %s',
            $this->pluginVersion->getName(),
            $this->pluginVersion->getVersion()
        );
    }

    #[\Override]
    public function execute(UpdateContext $context): void
    {
        $version = $this->installedVersion;
        assert($version instanceof PhpFilePluginVersion);

        $this->addPlugin($context, $this->pluginVersion, $version->getFilePath(), $version->getSignaturePath());
    }
}
