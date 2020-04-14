<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\ConfigLoader;
use Phpcq\Exception\RuntimeException;
use Phpcq\Output\SymfonyConsoleOutput;
use Phpcq\Output\SymfonyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function getcwd;
use function is_dir;
use function mkdir;
use function sprintf;

abstract class AbstractCommand extends Command
{
    /**
     * Only valid when examined from within doExecute().
     *
     * @var InputInterface
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $input;

    /**
     * Only valid when examined from within doExecute().
     *
     * @var OutputInterface
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $output;

    /**
     * Only valid when examined from within doExecute().
     *
     * @var string
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $phpcqPath;

    /**
     * Only valid when examined from within doExecute().
     *
     * @var array
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $config;

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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;

        $this->phpcqPath = $this->determinePhpcqPath();

        $configFile = $this->input->getOption('config');
        assert(is_string($configFile));
        $this->config = ConfigLoader::load($configFile);

        return $this->doExecute();
    }

    protected abstract function doExecute(): int;

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

    protected function getWrappedOutput(): \Phpcq\PluginApi\Version10\OutputInterface
    {
        // Wrap console output
        if ($this->output instanceof ConsoleOutputInterface) {
            return new SymfonyConsoleOutput($this->output);
        }
        return new SymfonyOutput($this->output);
    }

    private function determinePhpcqPath(): string
    {
        $phpcqPath = $this->input->getOption('tools');
        assert(is_string($phpcqPath));
        $this->createDirectory($phpcqPath);
        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('Using HOME: ' . $phpcqPath);
        }

        return $phpcqPath;
    }
}
