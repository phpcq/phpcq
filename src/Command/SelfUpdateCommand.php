<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Composer\Semver\Comparator;
use Phpcq\GnuPG\Downloader\KeyDownloader;
use Phpcq\GnuPG\GnuPGFactory;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\GnuPG\Signature\TrustedKeysStrategy;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Downloader\FileDownloader;
use Phpcq\Runner\Signature\InteractiveQuestionKeyTrustStrategy;
use Phpcq\Runner\Signature\SignatureFileDownloader;
use Phpcq\Runner\Updater\Composer\ComposerInstaller;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

use function assert;
use function file_get_contents;
use function is_bool;
use function is_dir;
use function is_string;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;

final class SelfUpdateCommand extends AbstractCommand
{
    /** @var string */
    private $pharFile;

    /**
     * Only valid when examined from within doExecute().
     *
     * @var DownloaderInterface
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $downloader;

    /**
     * Only valid when examined from within doExecute().
     *
     * @var Filesystem
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $filesystem;

    /**
     * @param string $pharFile The location of the execed phar file.
     */
    public function __construct(string $pharFile, ?DownloaderInterface $downloader = null)
    {
        parent::__construct();

        $this->pharFile = $pharFile;

        if ($downloader !== null) {
            $this->downloader = $downloader;
        }
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName('self-update')->setDescription('Updates the phpcq phar file');

        $this->addOption(
            'cache',
            'x',
            InputOption::VALUE_REQUIRED,
            'Path to the phpcq cache directory',
            (getenv('HOME') ?: sys_get_temp_dir()) . '/.cache/phpcq'
        );

        $this->addOption(
            'channel',
            null,
            InputOption::VALUE_REQUIRED,
            'The channel of the release. Right now only unstable is available',
            'unstable'
        );

        $this->addOption(
            'base-uri',
            null,
            InputOption::VALUE_REQUIRED,
            'The base uri of the phpcq releases',
            'https://phpcq.github.io/distrib/phpcq'
        );

        $this->addOption(
            'unsigned',
            null,
            InputOption::VALUE_NONE,
            'Disable signature checking'
        );

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Do not perform update, only check for a new release'
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force to update'
        );
    }

    protected function prepare(InputInterface $input): void
    {
        parent::prepare($input);

        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->downloader)) {
            $cachePath        = $this->getCachePath();
            $this->downloader = new FileDownloader($cachePath, $this->config->getAuth());
        }

        $this->filesystem = new Filesystem();
    }

    protected function doExecute(): int
    {
        $baseUri          = $this->getBaseUri();
        $installedVersion = $this->getInstalledVersion();
        $availableVersion = trim(substr($this->downloader->downloadFile($baseUri . '/current.txt', '', true), 6));

        $this->updateComposer();

        if (! $this->shouldUpdate($installedVersion, $availableVersion)) {
            return 0;
        }

        $pharUrl = $baseUri . '/phpcq.phar';
        $downloadedPhar = tempnam(sys_get_temp_dir(), 'phpcq.phar-');
        $this->output->writeln('Download phpcq.phar from ' . $pharUrl, OutputInterface::VERBOSITY_VERBOSE);
        $this->downloader->downloadFileTo($pharUrl, $downloadedPhar, '', true);

        $this->verifySignature($baseUri, $downloadedPhar);

        $this->filesystem->copy($downloadedPhar, $this->pharFile);
        $this->cleanup($downloadedPhar);

        return 0;
    }

    private function getCachePath(): string
    {
        $cachePath = $this->input->getOption('cache');
        assert(is_string($cachePath));
        $this->createDirectory($cachePath);

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('Using CACHE: ' . $cachePath);
        }

        return $cachePath;
    }

    private function getBaseUri(): string
    {
        $baseUri = $this->input->getOption('base-uri');
        assert(is_string($baseUri));

        $channel = $this->input->getOption('channel');
        assert(is_string($channel));

        return $baseUri . '/' . $channel;
    }

    private function getInstalledVersion(): string
    {
        $application = $this->getApplication();
        if (null === $application) {
            throw new RuntimeException('Application is not available');
        }

        return $application->getVersion();
    }

    private function shouldUpdate(string $installedVersion, string $availableVersion): bool
    {
        $dryRun = $this->input->getOption('dry-run');
        assert(is_bool($dryRun));
        $forced = $this->input->getOption('force');
        assert(is_bool($forced));

        if ($installedVersion === $availableVersion) {
            $this->output->writeln('Version <info>"' . $installedVersion . '"</info> already installed.');

            return !$dryRun && $forced;
        }

        if (Comparator::greaterThanOrEqualTo($installedVersion, $availableVersion)) {
            $this->output->writeln(
                sprintf(
                    'Installed version <info>"%s"</info> is newer than available version <info>"%s"</info>.',
                    $installedVersion,
                    $availableVersion
                )
            );

            return !$dryRun && $forced;
        }

        $this->output->writeln(
            sprintf(
                'Version <info>"%s"</info> installed. New version <info>"%s"</info> available.',
                $installedVersion,
                $availableVersion
            )
        );

        return !$dryRun;
    }

    private function verifySignature(string $baseUri, string $downloadedPhar): void
    {
        if ($this->input->getOption('unsigned')) {
            return;
        }

        try {
            $signature = $this->downloader->downloadFile($baseUri . '/phpcq.asc', '', true);
        } catch (RuntimeException $exception) {
            $this->cleanup($downloadedPhar);
            throw new RuntimeException('Unable to download signature file', 1, $exception);
        }

        $signatureVerifier = $this->createSignatureVerifier();
        $result            = $signatureVerifier->verify(file_get_contents($downloadedPhar), $signature);

        if (! $result->isValid()) {
            $this->cleanup($downloadedPhar);

            throw new RuntimeException('Signature verification failed.');
        }
    }

    private function createSignatureVerifier(): SignatureVerifier
    {
        $gnupgPath = $this->phpcqPath . '/gnupg';
        if (! is_dir($gnupgPath)) {
            $this->filesystem->mkdir($gnupgPath);
        }
        $questionHelper = $this->getHelper('question');
        assert($questionHelper instanceof QuestionHelper);

        $untrustedKeyStrategy = new InteractiveQuestionKeyTrustStrategy(
            new TrustedKeysStrategy($this->config->getTrustedKeys()),
            $this->input,
            $this->output,
            $questionHelper
        );

        return new SignatureVerifier(
            (new GnuPGFactory(sys_get_temp_dir()))->create($gnupgPath),
            new KeyDownloader(new SignatureFileDownloader($this->downloader, $this->output)),
            $untrustedKeyStrategy
        );
    }

    private function cleanup(string $downloadedPhar): void
    {
        $this->filesystem->remove($downloadedPhar);
    }

    private function updateComposer(): void
    {
        if ($this->input->getOption('dry-run')) {
            return;
        }

        $installer = new ComposerInstaller(
            $this->downloader,
            $this->output,
            $this->phpcqPath,
            $this->config->getComposer(),
            $this->findPhpCli()
        );

        if ($installer->isUpdatePossible()) {
            $this->output->writeln('Updating used composer.phar');
            $installer->update();
        }
    }
}
