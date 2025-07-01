<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Override;

final class TaskBuilder extends AbstractTaskBuilder
{
    /**
     * Create a new instance.
     *
     * @param list<string>         $command
     * @param array<string,string> $metadata
     */
    public function __construct(string $taskName, private readonly array $command, array $metadata)
    {
        parent::__construct($taskName, $metadata);
    }


    #[Override]
    protected function buildCommand(): array
    {
        return $this->command;
    }
}
