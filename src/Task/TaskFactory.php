<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\TaskFactoryInterface;
use Phpcq\PluginApi\Version10\TaskRunnerBuilderInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\Report\Report;
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
     * @var Report|null
     */
    private $report;

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
        ?Report $report,
        string $phpCliBinary,
        array $phpArguments
    ) {
        $this->phpcqPath    = $phpcqPath;
        $this->installed    = $installed;
        $this->phpCliBinary = $phpCliBinary;
        $this->phpArguments = $phpArguments;
        $this->report       = $report;
    }

    /**
     * @param string[] $command
     *
     * @return TaskRunnerBuilder
     */
    public function buildRunProcess(string $toolName, array $command): TaskRunnerBuilderInterface
    {
        return new TaskRunnerBuilder($toolName, $command, $this->report ? $this->createToolReport($toolName) : null);
    }

    /**
     * @param string   $toolName
     * @param string[] $arguments
     *
     * @return TaskRunnerBuilder
     */
    public function buildRunPhar(string $toolName, array $arguments = []): TaskRunnerBuilderInterface
    {
        return $this->buildRunProcess($toolName, array_merge(
            [$this->phpCliBinary],
            $this->phpArguments,
            [$this->phpcqPath . '/' . $this->installed->getTool($toolName, '*')->getPharUrl()],
            $arguments
        ));
    }

    public function createToolReport(string $toolName): ToolReportInterface
    {
        // Fixme: We need to tell the plugin if we transform the output.
        if ($this->report === null) {
            throw new RuntimeException(sprintf('No report assigned. Creating a tool report is not possible.'));
        }

        return $this->report->addToolReport($toolName);
    }
}
