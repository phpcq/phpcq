<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

final class RunCommand extends AbstractTaskCommand
{
    #[\Override]
    protected function configure(): void
    {
        $this->setName('run')->setDescription('Run configured build tasks');

        parent::configure();
    }
}
