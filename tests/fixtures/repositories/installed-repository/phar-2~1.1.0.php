<?php

use Phpcq\PluginApi\Version10\Definition\ExecTaskDefinitionBuilderInterface;
use Phpcq\PluginApi\Version10\EnvironmentInterface;
use Phpcq\PluginApi\Version10\PluginInterface;
use Phpcq\PluginApi\Version10\ExecPluginInterface;
use Phpcq\PluginApi\Version10\Task\TaskInterface;

return new class implements PluginInterface, ExecPluginInterface {
    public function getName(): string
    {
        return 'phar-2';
    }

    public function describeExecTask(
        ExecTaskDefinitionBuilderInterface $definitionBuilder,
        EnvironmentInterface $environment
    ): void {
        $definitionBuilder->describeApplication('Example 1', 'foo');
        $definitionBuilder->describeApplication('Example 2', 'bar');
    }

    public function createExecTask(
        ?string $application,
        array $arguments,
        EnvironmentInterface $environment
    ): TaskInterface {
        return $environment->getTaskFactory()->buildPhpProcess('foo')->build();
    }
};
