<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Composer\Semver\VersionParser;
use Phar;
use Phpcq\GnuPG\Downloader\KeyDownloader;
use Phpcq\GnuPG\GnuPGFactory;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\GnuPG\Signature\TrustedKeysStrategy;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\FileDownloader;
use Phpcq\Runner\Release;
use Phpcq\Runner\Signature\InteractiveQuestionKeyTrustStrategy;
use Phpcq\Runner\Signature\SignatureFileDownloader;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

use function assert;
use function file_get_contents;
use function is_bool;
use function is_dir;
use function is_string;
use function sprintf;
use function strnatcmp;
use function sys_get_temp_dir;
use function tempnam;

final class SelfUpdateCommand extends AbstractCommand
{
    /**
     * Only valid when examined from within doExecute().
     *
     * @var FileDownloader
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $downloader;

    /**
     * Only valid when examined from within doExecute().
     *
     * @var Filesystem
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $filesystem;

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
            'trunk',
            null,
            InputOption::VALUE_REQUIRED,
            'The trunk of the release. Right now only unstable is available',
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
            'If set there is no signature checking'
        );

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'If set the update is not performed but only checked for a new release'
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force to update'
        );
    }

    protected function doExecute(): int
    {
        $cachePath        = $this->getCachePath();
        $this->downloader = new FileDownloader($cachePath, $this->config->getAuth());
        $this->filesystem = new Filesystem();
        $baseUri          = $this->getBaseUri();
        $installedRelease = $this->getInstalledRelease();
        $current          = $this->downloader->downloadFile($baseUri . '/current.txt', '', true);
        $availableRelease = Release::fromString($current, 'phpcq ');

        if (! $this->shouldUpdate($installedRelease, $availableRelease)) {
            return 0;
        }

        $pharUrl = $baseUri . '/phpcq.phar';
        $downloadedPhar = tempnam(sys_get_temp_dir(), 'phpcq.phar-');
        $this->output->writeln('Download phpcq.phar from ' . $pharUrl, OutputInterface::VERBOSITY_VERBOSE);
        $this->downloader->downloadFileTo($pharUrl, $downloadedPhar, '', true);

        $this->verifySignature($baseUri, $downloadedPhar);

        $this->output->writeln(Phar::running(false));
        $this->filesystem->copy($downloadedPhar, Phar::running(false));
        $this->cleanup($downloadedPhar);

        return 0;
    }

    private function createSignatureVerifier(FileDownloader $downloader): SignatureVerifier
    {
        $gnupgPath = $this->phpcqPath . '/gnupg';
        if (! is_dir($gnupgPath)) {
            mkdir($gnupgPath, 0777, true);
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
            new KeyDownloader(new SignatureFileDownloader($downloader, $this->output)),
            $untrustedKeyStrategy
        );
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

        $trunk = $this->input->getOption('trunk');
        assert(is_string($trunk));

        return $baseUri . '/' . $trunk;
    }

    private function getInstalledRelease(): Release
    {
        $application = $this->getApplication();
        if (null === $application) {
            throw new RuntimeException('Application is not available');
        }

        return Release::fromString($application->getVersion());
    }

    private function shouldUpdate(Release $installedRelease, Release $availableRelease): bool
    {
        if ($this->input->getOption('force')) {
            return true;
        }

        if ($installedRelease->equals($availableRelease)) {
            $this->output->writeln('Already version "' . $installedRelease->getVersion() . '" installed');

            return false;
        }

        $versionParser    = new VersionParser();
        $installedVersion = $versionParser->normalize($installedRelease->getVersion());
        $availableVersion = $versionParser->normalize($availableRelease->getVersion());

        if (
            strnatcmp($installedVersion, $availableVersion) !== 0
            || $installedRelease->getBuildDate() <= $availableRelease->getBuildDate()
        ) {
            return false;
        }

        if ($this->input->getOption('no-interaction')) {
            $this->output->writeln(
                'Installed version is older than available version. Use <info>--force</info> option or use interaction.'
            );
            return false;
        }

        $helper = $this->getHelper('question');
        assert($helper instanceof QuestionHelper);

        $answer = $helper->ask(
            $this->input,
            $this->output,
            new ConfirmationQuestion(
                sprintf(
                    'Installed version has newer built date <info>%s</info> than available <info>%s</info>.'
                    . ' Really update <info>(y/n)</info>? ',
                    $installedRelease->getBuildDate()->format('Y-m-d H:i:s T'),
                    $availableRelease->getBuildDate()->format('Y-m-d H:i:s T')
                ),
                false
            )
        );
        assert(is_bool($answer));

        if (! $answer) {
            return false;
        }

        if ($this->input->getOption('dry-run')) {
            $this->output->writeln(
                sprintf(
                    'Version <info>"%s"</info> installed. Found latest <info>"%s"</info>.',
                    $installedRelease->getVersion(),
                    $availableRelease->getVersion()
                )
            );

            return false;
        }

        return true;
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

        $signatureVerifier = $this->createSignatureVerifier($this->downloader);
        $result            = $signatureVerifier->verify(file_get_contents($downloadedPhar), $signature);

        if (! $result->isValid()) {
            $this->cleanup($downloadedPhar);

            throw new RuntimeException('Signature verification failed.');
        }
    }

    private function cleanup(string $downloadedPhar): void
    {
        $this->filesystem->remove($downloadedPhar);
    }
}
