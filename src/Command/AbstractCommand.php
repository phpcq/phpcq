<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use function getcwd;
use function is_dir;
use function mkdir;
use function sprintf;
use function sys_get_temp_dir;

abstract class AbstractCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'The configuration file to use',
            getcwd() . '/.phpcq.yaml'
        );
        $this->addOption(
            'tools',
            't',
            InputOption::VALUE_REQUIRED,
            'Path to the phpcq tool directory',
            getcwd() . '/vendor/phpcq'
        );
        $this->addOption(
            'cache',
            'x',
            InputOption::VALUE_REQUIRED,
            'Path to the phpcq cache directory',
            (getenv('HOME') ?: sys_get_temp_dir()) . '/.cache/phpcq'
        );
    }

    /**
     * Create a directory if not exists.
     *
     * @param string $path The absolute directory path.
     *
     * @throws RuntimeException When creating directory fails
     */
    protected function createDirectory(string $path) : void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }
}
