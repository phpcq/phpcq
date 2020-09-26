<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Task\TaskBuilderInterface;
use Phpcq\PluginApi\Version10\Task\TaskFactoryInterface;
use Phpcq\Runner\Repository\InstalledPlugin;

class TaskFactory implements TaskFactoryInterface
{
    /**
     * The installed repository.
     *
     * @var InstalledPlugin
     */
    private $installed;

    /**
     * @var string
     */
    private $phpCliBinary;

    /**
     * @var string[]
     */
    private $phpArguments;

    /**
     * Create a new instance.
     *
     * @param InstalledPlugin $installed
     * @param string          $phpCliBinary
     * @param string[]        $phpArguments
     */
    public function __construct(
        InstalledPlugin $installed,
        string $phpCliBinary,
        array $phpArguments
    ) {
        $this->installed    = $installed;
        $this->phpCliBinary = $phpCliBinary;
        $this->phpArguments = $phpArguments;
    }

    /**
     * @param string[] $command
     *
     * @return TaskBuilder
     */
    public function buildRunProcess(string $toolName, array $command): TaskBuilderInterface
    {
        return new TaskBuilder($toolName, $command, $this->getMetadata($toolName));
    }

    /**
     * @param string   $toolName
     * @param string[] $arguments
     *
     * @return TaskBuilder
     */
    public function buildRunPhar(string $toolName, array $arguments = []): TaskBuilderInterface
    {
        $pharUrl = $this->installed->getTool($toolName)->getPharUrl();
        if (null === $pharUrl) {
            throw new RuntimeException('Tool ' . $toolName . ' does not have a phar');
        }

        return $this->buildRunProcess($toolName, array_merge(
            [$this->phpCliBinary],
            $this->phpArguments,
            [$pharUrl],
            $arguments
        ));
    }

    /**
     * @param string   $toolName
     * @param string[] $arguments
     *
     * @return TaskBuilder
     */
    public function buildPhpProcess(string $toolName, array $arguments = []): TaskBuilderInterface
    {
        return $this->buildRunProcess($toolName, array_merge(
            [$this->phpCliBinary],
            $this->phpArguments,
            $arguments
        ));
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
