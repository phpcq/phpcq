<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Config\PhpcqConfiguration;
use Phpcq\Runner\ConfigLoader;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Output\SymfonyConsoleOutput;
use Phpcq\Runner\Output\SymfonyOutput;
use Phpcq\PluginApi\Version10\Output\OutputInterface as PluginApiOutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

use Symfony\Component\Process\PhpExecutableFinder;
use function file_exists;
use function getcwd;
use function is_dir;
use function is_string;
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
     * @var PhpcqConfiguration
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
            'The configuration file to use. If not defined following paths are checked in the cwd: <i>.phpcq.yml, '
            . 'phpcq.yml, .phpcq.yml.dist, phpcq.yml.dist</i>'
        );
        $this->addOption(
            'home-dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Path to the phpcq home directory',
            getcwd() . '/.phpcq'
        );
        $this->addOption(
            'ignore-platform-reqs',
            null,
            InputOption::VALUE_NONE,
            'Ignore platform requirements (php & ext- packages).'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $this->prepare($input);

        return $this->doExecute();
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        $this->prepare($input);
        $this->doComplete($input, $suggestions);
    }

    protected function prepare(InputInterface $input): void
    {
        $this->input     = $input;
        $this->phpcqPath = $this->determinePhpcqPath();

        $configFile = $this->input->getOption('config');
        if (!is_string($configFile)) {
            $cwd = getcwd();
            foreach (['.phpcq.yaml', 'phpcq.yaml', '.phpcq.yaml.dist', 'phpcq.yaml.dist'] as $file) {
                $configFile = $cwd . '/' . $file;
                if (file_exists($configFile)) {
                    break;
                }
            }
            if (!is_string($configFile)) {
                throw new RuntimeException(
                    'Could not determine configuration file. File must be configured or exist in the cwd at the'
                    . ' following paths: .phpcq.yml, phpcq.yml, .phpcq.yml.dist or phpcq.yml.dist'
                );
            }
        }

        $this->config = ConfigLoader::load($configFile);
    }

    abstract protected function doExecute(): int;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function doComplete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
    }

    /**
     * Create a directory if not exists.
     *
     * @param string $path The absolute directory path.
     *
     * @throws RuntimeException When creating directory fails
     */
    protected function createDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }

    protected function getWrappedOutput(): PluginApiOutputInterface
    {
        // Wrap console output
        if ($this->output instanceof ConsoleOutputInterface) {
            return new SymfonyConsoleOutput($this->output);
        }
        return new SymfonyOutput($this->output);
    }

    private function determinePhpcqPath(): string
    {
        $phpcqPath = $this->input->getOption('home-dir');
        assert(is_string($phpcqPath));
        $this->createDirectory($phpcqPath);

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if ($this->output && $this->output->isVeryVerbose()) {
            $this->output->writeln('Using HOME: ' . $phpcqPath);
        }

        return $phpcqPath;
    }

    protected function getWrapWidth(): int
    {
        if ($this->output instanceof ConsoleOutputInterface) {
            return (new Terminal())->getWidth();
        }

        return 80;
    }

    protected function getPluginPath(): string
    {
        return $this->phpcqPath . '/plugins';
    }

    /** @psalm-return array{string, list<string>} */
    protected function findPhpCli(): array
    {
        $finder     = new PhpExecutableFinder();
        $executable = $finder->find();

        if (!is_string($executable)) {
            throw new RuntimeException('PHP executable not found');
        }
        /** @psalm-var list<string> $arguments */
        $arguments = $finder->findArguments();

        return [$executable, $arguments];
    }
}
