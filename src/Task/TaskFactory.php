<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Phpcq\PluginApi\Version10\Task\PhpTaskBuilderInterface;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Task\TaskBuilderInterface;
use Phpcq\PluginApi\Version10\Task\TaskFactoryInterface;
use Phpcq\Runner\Repository\InstalledPlugin;

class TaskFactory implements TaskFactoryInterface
{
    /**
     * Create a new instance.
     *
     * @param InstalledPlugin $installed
     * @param string          $phpCliBinary
     * @param list<string>    $phpArguments
     */
    public function __construct(
        private readonly string $taskName,
        /**
         * The installed plugin.
         */
        private readonly InstalledPlugin $installed,
        private readonly string $phpCliBinary,
        private readonly array $phpArguments,
        private readonly bool $tty = false
    ) {
    }

    /**
     * @param string[] $command
     *
     * @return TaskBuilder
     */
    #[\Override]
    public function buildRunProcess(string $toolName, array $command): TaskBuilderInterface
    {
        $builder = new TaskBuilder($this->taskName, array_values($command), $this->getMetadata($toolName));

        if ($this->tty) {
            return $builder->withTty();
        }

        return $builder;
    }

    /**
     * @param string   $toolName
     * @param string[] $arguments
     *
     * @return TaskBuilderPhp
     */
    #[\Override]
    public function buildRunPhar(string $toolName, array $arguments = []): PhpTaskBuilderInterface
    {
        $pharUrl = $this->installed->getTool($toolName)->getPharUrl();
        if (null === $pharUrl) {
            throw new RuntimeException('Tool ' . $toolName . ' does not have a phar');
        }

        return $this->buildPhpProcess($toolName, array_merge([$pharUrl], $arguments));
    }

    /**
     * @param string   $toolName
     * @param string[] $arguments
     *
     * @return TaskBuilderPhp
     */
    #[\Override]
    public function buildPhpProcess(string $toolName, array $arguments = []): PhpTaskBuilderInterface
    {
        return new TaskBuilderPhp(
            $this->taskName,
            $this->phpCliBinary,
            $this->phpArguments,
            array_values($arguments),
            $this->getMetadata($toolName)
        );
    }

    /** @return array<string,string> */
    private function getMetadata(string $toolName): array
    {
        $metadata = [
            'plugin_name'     => $this->installed->getName(),
            'plugin_version' => $this->installed->getPluginVersion()->getVersion(),
        ];

        if ($this->installed->hasTool($toolName)) {
            $tool                     = $this->installed->getTool($toolName);
            $metadata['tool_name']    = $tool->getName();
            $metadata['tool_version'] = $tool->getVersion();
        }

        return $metadata;
    }
}
