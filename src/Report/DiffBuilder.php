<?php

declare(strict_types=1);

namespace Phpcq\Report;

use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\Report\DiffBuilderInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Report\Buffer\DiffBuffer;
use Symfony\Component\Filesystem\Filesystem;

final class DiffBuilder implements DiffBuilderInterface
{
    /** @var string */
    private $name;

    /** @var string|null */
    private $absolutePath;

    /** @var TaskReportInterface */
    private $parent;

    /** @var string */
    private $tempDir;

    /** @var Filesystem */
    private $filesystem;

    /**
     * @var callable
     * @psalm-var callable(DiffBuffer, DiffBuilder): void
     */
    private $callback;

    /** @psalm-param callable(DiffBuffer, DiffBuilder): void $callback */
    public function __construct(
        string $name,
        TaskReportInterface $parent,
        string $tempDir,
        Filesystem $filesystem,
        callable $callback
    ) {
        $this->parent   = $parent;
        $this->tempDir  = $tempDir;
        $this->name     = $name;
        $this->callback = $callback;
        $this->filesystem = $filesystem;
    }

    public function fromFile(string $file): DiffBuilderInterface
    {
        if ('/' !== $file[0]) {
            throw new RuntimeException('Absolute path expected but got: "' . $file . '"');
        }

        $this->absolutePath = $file;

        return $this;
    }

    public function fromString(string $buffer): DiffBuilderInterface
    {
        // NOTE: We do not unlink the previous file on purpose here of already set.
        //       this is due to two reasons:
        //       1. we would be potentially unlinking a file passed via "fromFile".
        //       2. it is the job of the post run cleanup to remove all temporary artifacts.
        $this->absolutePath = $this->tempDir . '/' . uniqid($this->name);

        $this->filesystem->dumpFile($this->absolutePath, $buffer);

        return $this;
    }

    public function end(): TaskReportInterface
    {
        if (null === $this->absolutePath) {
            throw new RuntimeException('Must provide content either via fromFile() or via fromString()');
        }

        call_user_func(
            $this->callback,
            new DiffBuffer($this->absolutePath, $this->name),
            $this
        );

        return $this->parent;
    }
}
