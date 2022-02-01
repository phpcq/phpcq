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
     * The installed plugin.
     *
     * @var InstalledPlugin
     */
    private $installed;

    /** @var string */
    private $taskName;

    /** @var string */
    private $phpCliBinary;

    /** @var list<string> */
    private $phpArguments;

    /**
     * Create a new instance.
     *
     * @param InstalledPlugin $installed
     * @param string          $phpCliBinary
     * @param list<string>    $phpArguments
     */
    public function __construct(
        string $taskName,
        InstalledPlugin $installed,
        string $phpCliBinary,
        array $phpArguments
    ) {
        $this->taskName     = $taskName;
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
        return new TaskBuilder($this->taskName, array_values($command), $this->getMetadata($toolName));
    }

    /**
     * @param string   $toolName
     * @param string[] $arguments
     *
     * @return TaskBuilderPhp
     */
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
