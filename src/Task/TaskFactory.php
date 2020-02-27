<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\Repository\RepositoryInterface;

class TaskFactory
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
     * Create a new instance.
     *
     * @param string              $phpcqPath
     * @param RepositoryInterface $installed
     * @param string              $phpCliBinary
     */
    public function __construct(string $phpcqPath, RepositoryInterface $installed, string $phpCliBinary)
    {
        $this->phpcqPath    = $phpcqPath;
        $this->installed    = $installed;
        $this->phpCliBinary = $phpCliBinary;
    }

    /**
     * @param string[] $command
     *
     * @return TaskRunnerBuilder
     */
    public function buildRunProcess(array $command): TaskRunnerBuilder
    {
        return new TaskRunnerBuilder($command);
    }

    /**
     * @param string   $pharName
     * @param string[] $arguments
     *
     * @return TaskRunnerBuilder
     */
    public function buildRunPhar(string $pharName, array $arguments = []): TaskRunnerBuilder
    {
        return new TaskRunnerBuilder([
            $this->phpCliBinary,
            $this->phpcqPath . '/' . $this->installed->getTool($pharName, '*')->getPharUrl()
        ] + $arguments);
    }
}
