<?php

declare(strict_types=1);

namespace Phpcq\PostProcessor;

use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\Report\Report;

interface PostProcessorInterface
{
    public function process(Report $report, OutputInterface $output): void;
}
