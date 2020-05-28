<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\PluginApi\Version10\TaskFactoryInterface;
use Phpcq\PluginApi\Version10\TaskRunnerBuilderInterface;
use Phpcq\PluginApi\Version10\TaskRunnerInterface;
use Phpcq\PostProcessor\CheckstyleFilePostProcessor;
use Phpcq\PostProcessor\PostProcessorInterface;
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
     * @var Report
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
        Report $report,
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
    public function buildRunProcess(array $command): TaskRunnerBuilderInterface
    {
        return new TaskRunnerBuilder($command);
    }

    /**
     * @param string   $pharName
     * @param string[] $arguments
     *
     * @return TaskRunnerBuilder
     */
    public function buildRunPhar(string $pharName, array $arguments = []): TaskRunnerBuilderInterface
    {
        return $this->buildRunProcess(array_merge(
            [$this->phpCliBinary],
            $this->phpArguments,
            [$this->phpcqPath . '/' . $this->installed->getTool($pharName, '*')->getPharUrl()],
            $arguments
        ));
    }

    // TODO: Do we need a PostProcessorTaskRunnerBuilder?
    public function buildPostProcessor(PostProcessorInterface $postProcessor): TaskRunnerInterface
    {
        return new PostProcessTaskRunner($postProcessor, $this->report);
    }

    public function buildCheckStyleFilePostProcessor(string $toolName, string $checkFilePath): TaskRunnerInterface
    {
        return new PostProcessTaskRunner(
            new CheckstyleFilePostProcessor($toolName, $checkFilePath),
            $this->report
        );
    }
}
