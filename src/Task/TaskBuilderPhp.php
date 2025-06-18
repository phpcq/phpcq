<?php

declare(strict_types=1);

namespace Phpcq\Runner\Task;

use Phpcq\PluginApi\Version10\Task\PhpTaskBuilderInterface;

final class TaskBuilderPhp extends AbstractTaskBuilder implements PhpTaskBuilderInterface
{
    /**
     * @var bool
     */
    private $disableXDebug = false;

    /**
     * Create a new instance.
     *
     * @param string               $phpCliBinary
     * @param list<string>         $phpArguments
     * @param list<string>         $arguments
     * @param array<string,string> $metadata
     */
    public function __construct(
        string $taskName,
        private readonly string $phpCliBinary,
        private readonly array $phpArguments,
        private readonly array $arguments,
        array $metadata
    ) {
        parent::__construct($taskName, $metadata);
    }

    #[\Override]
    public function withoutXDebug(): PhpTaskBuilderInterface
    {
        $this->disableXDebug = true;

        return $this;
    }

    #[\Override]
    protected function buildCommand(): array
    {
        $phpArguments = $this->phpArguments;
        if ($this->disableXDebug) {
            $phpArguments[] = '-dxdebug.mode=off';
        }

        return array_merge(
            [$this->phpCliBinary],
            $phpArguments,
            $this->arguments
        );
    }
}
