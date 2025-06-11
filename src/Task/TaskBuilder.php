<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

final class TaskBuilder extends AbstractTaskBuilder
{
    /** @var list<string> */
    private $command;

    /**
     * Create a new instance.
     *
     * @param list<string>         $command
     * @param array<string,string> $metadata
     */
    public function __construct(string $taskName, array $command, array $metadata)
    {
        parent::__construct($taskName, $metadata);
        $this->command  = $command;
    }


    #[\Override]
    protected function buildCommand(): array
    {
        return $this->command;
    }
}
