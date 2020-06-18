<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\PluginApi\Version10\Task\TaskBuilderInterface;
use Phpcq\PluginApi\Version10\Task\TaskFactoryInterface;
use Phpcq\Repository\RepositoryInterface;

class TaskFactory implements TaskFactoryInterface
{
    /**
     * The installed repository.
     *
     * @var RepositoryInterface
     */
    private $installed;

    /**
     * @var string
     */
    private $phpCliBinary;

    /**
     * @var string
     */
    private $phpcqPath;

    /**
     * @var string[]
     */
    private $phpArguments;

    /**
     * Create a new instance.
     *
     * @param string              $phpcqPath
     * @param RepositoryInterface $installed
     * @param string              $phpCliBinary
     * @param string[]            $phpArguments
     */
    public function __construct(
        string $phpcqPath,
        RepositoryInterface $installed,
        string $phpCliBinary,
        array $phpArguments
    ) {
        $this->phpcqPath    = $phpcqPath;
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
        return new TaskBuilder($toolName, $command);
    }

    /**
     * @param string   $toolName
     * @param string[] $arguments
     *
     * @return TaskBuilder
     */
    public function buildRunPhar(string $toolName, array $arguments = []): TaskBuilderInterface
    {
        return $this->buildRunProcess($toolName, array_merge(
            [$this->phpCliBinary],
            $this->phpArguments,
            [$this->phpcqPath . '/' . $this->installed->getTool($toolName, '*')->getPharUrl()],
            $arguments
        ));
    }
}
