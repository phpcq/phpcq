<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Tool;

use Phpcq\Runner\Updater\Task\HashValidator;
use Phpcq\Runner\Updater\UpdateContext;

final class InstallToolTask extends AbstractInstallingToolTask
{
    use HashValidator;

    public function getPurposeDescription(): string
    {
        return sprintf(
            'Will install tool %s in version %s',
            $this->toolVersion->getName(),
            $this->toolVersion->getVersion()
        );
    }

    public function getExecutionDescription(): string
    {
        return sprintf(
            'Installing tool %s version %s',
            $this->toolVersion->getName(),
            $this->toolVersion->getVersion()
        );
    }

    public function execute(UpdateContext $context): void
    {
        $this->install($context);
    }
}
