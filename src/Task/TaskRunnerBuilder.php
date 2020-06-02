<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\Output\SymfonyOutput;
use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\PluginApi\Version10\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\OutputTransformerInterface;
use Phpcq\PluginApi\Version10\PostProcessorInterface;
use Phpcq\PluginApi\Version10\TaskRunnerBuilderInterface;
use Phpcq\PluginApi\Version10\TaskRunnerInterface;
use Phpcq\PluginApi\Version10\ToolReportInterface;
use Phpcq\PostProcessor\ConsoleOutputToolReportProcessor;
use Traversable;

final class TaskRunnerBuilder implements TaskRunnerBuilderInterface
{
    /**
     * @var string
     */
    private $toolName;

    /**
     * @var string[]
     */
    private $command;

    /**
     * @var string|null
     */
    private $cwd = null;

    /**
     * @var string[]|null
     */
    private $env = null;

    /**
     * @var resource|string|Traversable|null
     */
    private $input = null;

    /**
     * @var int|float|null
     */
    private $timeout = null;

    /**
     * @var OutputTransformerFactoryInterface|null
     */
    private $transformerFactory;

    /**
     * @var PostProcessorInterface|null
     * @deprecated
     */
    private $postProcessor;

    /**
     * @var ToolReportInterface
     */
    private $report;

    /**
     * Create a new instance.
     *
     * @param string[] $command
     */
    public function __construct(string $toolName, array $command, ToolReportInterface $report)
    {
        $this->toolName = $toolName;
        $this->command = $command;
        $this->report = $report;
    }

    public function withWorkingDirectory(string $cwd): TaskRunnerBuilderInterface
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * @param string[] $env
     */
    public function withEnv(array $env): TaskRunnerBuilderInterface
    {
        $this->env = $env;

        return $this;
    }

    /**
     * @param resource|string|Traversable $input The input as stream resource, scalar or \Traversable, or null for no
     *                                           input
     */
    public function withInput($input): TaskRunnerBuilderInterface
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param int|float $timeout
     */
    public function withTimeout($timeout): TaskRunnerBuilderInterface
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function withOutputTransformer(OutputTransformerFactoryInterface $factory): TaskRunnerBuilderInterface
    {
        $this->transformerFactory = $factory;

        return $this;
    }

    /** @deprecated */
    public function withPostProcessor(PostProcessorInterface $postProcessor): TaskRunnerBuilderInterface
    {
        $this->postProcessor = $postProcessor;

        return $this;
    }

    public function build(): TaskRunnerInterface
    {
        $transformerFactory = $this->transformerFactory;
        if (null === $transformerFactory) {
            $postProcessor = $this->postProcessor ?: new ConsoleOutputToolReportProcessor($this->toolName);

            $transformerFactory = new class ($postProcessor) implements OutputTransformerFactoryInterface {
                private $postProcessor;

                public function __construct(PostProcessorInterface $postProcessor)
                {
                    $this->postProcessor = $postProcessor;
                }

                public function createFor(ToolReportInterface $report): OutputTransformerInterface
                {
                    return new class($this->postProcessor, $report) implements OutputTransformerInterface {
                        /** @var ToolReportInterface */
                        private $report;

                        private $data = [
                            OutputInterface::CHANNEL_STDOUT => '',
                            OutputInterface::CHANNEL_STDERR => '',
                        ];

                        private $postProcessor;

                        public function __construct(PostProcessorInterface $postProcessor, ToolReportInterface $report)
                        {
                            $this->postProcessor = $postProcessor;
                            $this->report        = $report;
                        }

                        public function write(string $data, int $channel): void
                        {
                            $this->data[$channel] .= $data;
                        }

                        public function finish(int $exitCode): void
                        {
                            $this->postProcessor->process(
                                $this->report,
                                $this->data[OutputInterface::CHANNEL_STDOUT],
                                $exitCode,
                                // FIXME: this kills the output from the post processors - need to handle better or remove post processors.
                                new SymfonyOutput(new \Symfony\Component\Console\Output\BufferedOutput())
                            );
                            $this->report = null;
                        }
                    };
                }
            };
        }

        return new ProcessTaskRunner(
            $this->command,
            $this->report,
            $transformerFactory,
            $this->cwd,
            $this->env,
            $this->input,
            $this->timeout
        );
    }
}
