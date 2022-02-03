<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Composer;

use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

use function array_merge;
use function file_exists;
use function getcwd;
use function hash_file;
use function sprintf;
use function unlink;

final class ComposerInstaller
{
    /** @var string */
    private $installDir;

    /** @var array{0:string, 1: list<string>} */
    private $phpCli;

    /** @var DownloaderInterface */
    private $downloader;

    /**
     * @var array
     */
    private $composerConfig;

    /**
     * @var OutputInterface
     */
    private $output;

    /** @param array{0:string, 1: list<string>} $phpCli */
    public function __construct(
        DownloaderInterface $downloader,
        OutputInterface $output,
        string $installDir,
        array $composerConfig,
        array $phpCli
    ) {
        $this->installDir     = $installDir;
        $this->output         = $output;
        $this->phpCli         = $phpCli;
        $this->downloader     = $downloader;
        $this->composerConfig = $composerConfig;
    }

    /** @return list<string> */
    public function install(): array
    {
        if ($this->composerConfig['autodiscover']) {
            $composerPath = $this->autoDiscoverComposer();
        } else {
            $composerPath = $this->downloadComposer();
        }

        return array_merge([$this->phpCli[0]], $this->phpCli[1], [$composerPath, '--no-ansi', '--no-interaction']);
    }

    public function update(): void
    {
        // Do not update if using auto discovered composer
        if ($this->composerConfig['autodiscover']) {
            $this->output->writeln(
                'Auto discovered composer is used. Do not update composer',
                OutputInterface::VERBOSITY_VERY_VERBOSE
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

            return;
        }

        $this->downloadComposer();
    }

    private function autoDiscoverComposer(): string
    {
        $executableFinder = new ExecutableFinder();
        $executableFinder->addSuffix('');
        $executableFinder->addSuffix('.phar');

        $composerPath = $executableFinder->find('composer', null, [getcwd()]);
        if ($composerPath === null) {
            throw new RuntimeException('Unable to autodiscover composer binary');
        }

        return $composerPath;
    }

    private function downloadComposer(): string
    {
        if (file_exists($this->installDir . '/composer.phar')) {
            $this->output->writeln(
                '<info>composer.phar</info> is already installed',
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            return $this->installDir . '/composer.phar';
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

        return $this->installDir . '/composer.phar';
    }
}
