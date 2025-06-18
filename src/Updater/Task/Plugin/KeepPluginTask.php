<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Plugin;

use Override;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Updater\UpdateContext;

use function assert;
use function sprintf;

final class KeepPluginTask extends AbstractPluginTask
{
    public function __construct(
        PluginVersionInterface $pluginVersion,
        private readonly PluginVersionInterface $installedVersion
    ) {
        parent::__construct($pluginVersion);
    }

    #[Override]
    public function getPurposeDescription(): string
    {
        return sprintf(
            'Will keep plugin %s in version %s',
            $this->pluginVersion->getName(),
            $this->pluginVersion->getVersion()
        );
    }

    #[Override]
    public function getExecutionDescription(): string
    {
        return sprintf(
            'Keeping plugin %s in version %s',
            $this->pluginVersion->getName(),
            $this->pluginVersion->getVersion()
        );
    }

    #[Override]
    public function execute(UpdateContext $context): void
    {
        $version = $this->installedVersion;
        assert($version instanceof PhpFilePluginVersion);

        $this->addPlugin($context, $this->pluginVersion, $version->getFilePath(), $version->getSignaturePath());
    }
}
