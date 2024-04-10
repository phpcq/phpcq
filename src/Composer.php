<?php

declare(strict_types=1);

namespace Phpcq\Runner;

use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

use function array_merge;
use function assert;
use function file_exists;
use function getcwd;
use function hash_file;
use function is_array;
use function sprintf;
use function unlink;
use function strpos;

/**
 * @psalm-import-type TComposerConfig from \Phpcq\Runner\Config\PhpcqConfiguration
 */
class Composer
{
    /** @var OutputInterface */
    private $output;

    /** @var Filesystem */
    private $filesystem;

    /** @var DownloaderInterface */
    private $downloader;

    /** @var string */
    private $installDir;

    /**
     * @var array
     * @psalm-var TComposerConfig
     */
    private $composerConfig;

    /** @var array{0:string, 1: list<string>} */
    private $phpCli;

    /** @var list<string>|null */
    private $command;

    /**
     * @psalm-param array{0:string, 1: list<string>} $phpCli
     * @psalm-param TComposerConfig                  $composerConfig
     */
    public function __construct(
        DownloaderInterface $downloader,
        Filesystem $filesystem,
        OutputInterface $output,
        string $installDir,
        array $composerConfig,
        array $phpCli
    ) {
        $this->output         = $output;
        $this->filesystem     = $filesystem;
        $this->downloader     = $downloader;
        $this->installDir     = $installDir;
        $this->composerConfig = $composerConfig;
        $this->phpCli         = $phpCli;
    }

    public function isBinaryAutoDiscovered(): bool
    {
        return $this->composerConfig['autodiscover'];
    }

    public function installBinary(): void
    {
        if ($this->composerConfig['autodiscover']) {
            // Try is composer is available
            $this->autoDiscoverComposer();

            return;
        }

        $this->downloadComposer();
    }

    public function updateBinary(): void
    {
        // Do not update if using auto discovered composer
        if ($this->composerConfig['autodiscover']) {
            $this->output->writeln(
                'Auto discovered composer is used. Do not update composer',
                \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            return;
        }

        if (file_exists($this->installDir . '/composer.phar')) {
            $command = array_merge(
                [$this->phpCli[0]],
                $this->phpCli[1],
                [$this->installDir . '/composer.phar', '--no-ansi', '--no-interaction', 'self-update']
            );
            $process = new Process($command, $this->installDir);
            $process->mustRun();

            $this->output->writeln($process->getOutput(), OutputInterface::VERBOSITY_VERBOSE);
            $this->output->writeln($process->getErrorOutput(), OutputInterface::VERBOSITY_VERBOSE);

            return;
        }

        $this->downloadComposer();
    }

    public function installDependencies(string $targetDirectory): void
    {
        $this->execute(['install', '--optimize-autoloader', '--no-progress'], $targetDirectory);
    }

    public function updateDependencies(string $targetDirectory): void
    {
        $this->execute(['update', '--optimize-autoloader', '--no-progress'], $targetDirectory);
    }

    public function isUpdateRequired(string $targetDirectory): bool
    {
        $this->initialize();

        $composerJsonExists = $this->filesystem->exists($targetDirectory . '/composer.json');

        // No composer.json exist, update required
        if (!$composerJsonExists) {
            return true;
        }

        // Try composer update dry-run
        try {
            $process = $this->createProcess(['update', '--dry-run', '--no-progress'], $targetDirectory);
            $process->mustRun();
            $output  = $process->getOutput() ?: $process->getErrorOutput();
        } catch (ProcessFailedException $exception) {
            return true;
        }

        return strpos($output, 'Nothing to install, update or remove') === false;
    }

    private function execute(array $command, string $targetDirectory): void
    {
        $this->initialize();

        try {
            $process = $this->createProcess($command, $targetDirectory);
            $process->mustRun();

            $this->output->write($process->getOutput(), OutputInterface::VERBOSITY_VERBOSE);
            $this->output->write($process->getErrorOutput(), OutputInterface::VERBOSITY_VERBOSE);
        } catch (ProcessFailedException $exception) {
            /** @psalm-suppress MixedAssignment */
            $process = $exception->getProcess();
            assert($process instanceof Process);

            $this->output->write(
                $process->getErrorOutput(),
                OutputInterface::VERBOSITY_VERBOSE,
                OutputInterface::CHANNEL_STDERR
            );

            $this->output->write(
                $process->getOutput(),
                OutputInterface::VERBOSITY_VERBOSE,
                OutputInterface::CHANNEL_STDERR
            );

            throw $exception;
        }
    }

    private function initialize(): void
    {
        if ($this->command !== null) {
            return;
        }

        if ($this->composerConfig['autodiscover']) {
            $composerPath = $this->autoDiscoverComposer();
        } else {
            $composerPath = $this->installDir . '/composer.phar';
            if (!file_exists($composerPath)) {
                throw new RuntimeException('Composer.phar is not installed. Run phpcq self-update to install it');
            }
        }

        $this->command = array_merge(
            [$this->phpCli[0]],
            $this->phpCli[1],
            [$composerPath, '--no-ansi', '--no-interaction'],
        );
    }

    private function createProcess(array $command, string $targetDirectory): Process
    {
        assert(is_array($this->command));

        return new Process(array_merge($this->command, $command), $targetDirectory, null, null, null);
    }

    private function downloadComposer(): void
    {
        if (file_exists($this->installDir . '/composer.phar')) {
            $this->output->writeln(
                '<info>composer.phar</info> is already installed',
                \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            return;
        }

        $this->output->writeln('Installing composer.phar', OutputInterface::VERBOSITY_VERBOSE);
        $setupFile = $this->installDir . '/composer-setup.php';
        $this->downloader->downloadFileTo('https://getcomposer.org/installer', $setupFile);
        $expectedChecksum = $this->downloader->downloadFile('https://composer.github.io/installer.sig');
        $actualChecksum   = hash_file('sha384', $setupFile);

        if ($expectedChecksum !== $actualChecksum) {
            unlink($setupFile);

            throw new RuntimeException(
                sprintf('Invalid checksum given. Expected "%s" got "%s".', $expectedChecksum, $actualChecksum)
            );
        }

        try {
            $command = array_merge([$this->phpCli[0]], $this->phpCli[1], [$setupFile]);
            $process = new Process($command, $this->installDir);
            $process->mustRun();

            unlink($setupFile);
        } catch (Throwable $exception) {
            unlink($setupFile);
            throw $exception;
        }

        if (!file_exists($this->installDir . '/composer.phar')) {
            throw new RuntimeException('Installing composer.phar failed');
        }
    }

    private function autoDiscoverComposer(): string
    {
        $executableFinder = new ExecutableFinder();
        $executableFinder->addSuffix('');
        $executableFinder->addSuffix('.phar');

        $composerPath = $executableFinder->find('composer', null, [getcwd()]);
        if ($composerPath === null) {
            throw new RuntimeException('Unable to auto discover composer binary');
        }

        return $composerPath;
    }
}
