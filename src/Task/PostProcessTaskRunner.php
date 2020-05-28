<?php

declare(strict_types=1);

namespace Phpcq\Task;

use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\PluginApi\Version10\TaskRunnerInterface;
use Phpcq\PostProcessor\PostProcessorInterface;
use Phpcq\Report\Report;

final class PostProcessTaskRunner implements TaskRunnerInterface
{
    /** @var PostProcessorInterface */
    private $postProcessor;

    /** @var Report */
    private $report;

    public function __construct(PostProcessorInterface $postProcessor, Report $report)
    {
        $this->postProcessor = $postProcessor;
        $this->report        = $report;
    }

    public function run(OutputInterface $output): void
    {
        $this->postProcessor->process($this->report, $output);
    }
}
