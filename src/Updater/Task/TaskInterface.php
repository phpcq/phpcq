<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task;

use Phpcq\Runner\Updater\UpdateContext;

interface TaskInterface
{
    public function getPluginName(): string;

    public function getPurposeDescription(): string;

    public function getExecutionDescription(): string;

    public function execute(UpdateContext $context): void;
}
