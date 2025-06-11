<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Tool;

use Phpcq\Runner\Updater\UpdateContext;

final class RemoveToolTask extends AbstractToolTask
{
    #[\Override]
    public function getPurposeDescription(): string
    {
        return sprintf(
            'Will remove tool %s in version %s',
            $this->toolVersion->getName(),
            $this->toolVersion->getVersion()
        );
    }

    #[\Override]
    public function getExecutionDescription(): string
    {
        return sprintf(
            'Removing tool %s in version %s',
            $this->toolVersion->getName(),
            $this->toolVersion->getVersion()
        );
    }

    #[\Override]
    public function execute(UpdateContext $context): void
    {
        if ($url = $this->toolVersion->getPharUrl()) {
            $context->filesystem->remove($url);
        }
        if ($url = $this->toolVersion->getSignatureUrl()) {
            $context->filesystem->remove($url);
        }
    }
}
