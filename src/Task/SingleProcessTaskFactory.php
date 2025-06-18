<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Phpcq\PluginApi\Version10\Task\PhpTaskBuilderInterface;
use Phpcq\PluginApi\Version10\Task\TaskBuilderInterface;
use Phpcq\PluginApi\Version10\Task\TaskFactoryInterface;

class SingleProcessTaskFactory implements TaskFactoryInterface
{
    public function __construct(private readonly TaskFactoryInterface $factory)
    {
    }

    #[\Override]
    public function buildRunProcess(string $toolName, array $command): TaskBuilderInterface
    {
        return $this->factory->buildRunProcess($toolName, $command)->forceSingleProcess();
    }

    #[\Override]
    public function buildRunPhar(string $toolName, array $arguments = []): PhpTaskBuilderInterface
    {
        $taskBuilder = $this->factory->buildRunPhar($toolName, $arguments)->forceSingleProcess();
        assert($taskBuilder instanceof PhpTaskBuilderInterface);

        return $taskBuilder;
    }

    #[\Override]
    public function buildPhpProcess(string $toolName, array $arguments = []): PhpTaskBuilderInterface
    {
        $taskBuilder = $this->factory->buildPhpProcess($toolName, $arguments)->forceSingleProcess();
        assert($taskBuilder instanceof PhpTaskBuilderInterface);

        return $taskBuilder;
    }
}
