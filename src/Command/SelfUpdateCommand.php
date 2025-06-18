<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Composer\Semver\Comparator;
use Phar;
use Phpcq\GnuPG\Downloader\KeyDownloader;
use Phpcq\GnuPG\GnuPGFactory;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\GnuPG\Signature\TrustedKeysStrategy;
use Phpcq\Runner\Composer;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Downloader\FileDownloader;
use Phpcq\Runner\Platform\PlatformRequirementChecker;
use Phpcq\Runner\Platform\PlatformRequirementCheckerInterface;
use Phpcq\Runner\SelfUpdate\Version;
use Phpcq\Runner\SelfUpdate\VersionsRepositoryLoader;
use Phpcq\Runner\Signature\InteractiveQuestionKeyTrustStrategy;
use Phpcq\Runner\Signature\SignatureFileDownloader;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
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

    private PlatformRequirementCheckerInterface $requirementChecker;

    /**
     * @param string $pharFile The location of the execed phar file.
     */
    public function __construct(
        string $pharFile,
        ?DownloaderInterface $downloader = null,
        ?PlatformRequirementCheckerInterface $requirementChecker = null
    ) {
        parent::__construct();

        $this->pharFile = $pharFile;

        if ($downloader !== null) {
            $this->downloader = $downloader;
        }

        $this->requirementChecker = $requirementChecker ?: PlatformRequirementChecker::create();
    }

    #[\Override]
    protected function configure(): void
    {
        parent::configure();

        $this->setName('self-update')->setDescription('Updates the phpcq phar file');

        $this->addArgument('version', InputArgument::OPTIONAL, 'The version constraint to update to');

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
            'The channel of the release.',
            'stable'
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

    #[\Override]
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

    #[\Override]
    protected function doExecute(): int
    {
        $this->updateComposer();

        if (! $this->pharFile) {
            $this->output->writeln('No running phar detected. Abort self-update', OutputInterface::VERBOSITY_VERBOSE);
            return 0;
        }

        $baseUri          = $this->getBaseUri();
        $installedVersion = $this->getInstalledVersion();
        $repository       = (new VersionsRepositoryLoader($this->requirementChecker, $this->downloader))
            ->load($baseUri . '/versions.json');

        $this->updateComposer();

        /** @psalm-var string|null $requiredVersion */
        $requiredVersion = $this->input->getArgument('version') ?: null;
        $version         = $repository->findMatchingVersion($requiredVersion, ! $this->input->getOption('unsigned'));

        if (!$this->shouldUpdate($installedVersion, $version->getVersion())) {
            return 0;
        }

        $pharUrl        = $this->getBaseUri() . '/' . $version->getPharFile();
        $downloadedPhar = tempnam(sys_get_temp_dir(), 'phpcq.phar-');
        $this->output->writeln('Download phpcq.phar from ' . $pharUrl, OutputInterface::VERBOSITY_VERBOSE);
        $this->downloader->downloadFileTo($pharUrl, $downloadedPhar, '', true);

        $this->verifySignature($version, $downloadedPhar);

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

    private function verifySignature(Version $version, string $downloadedPhar): void
    {
        if ($this->input->getOption('unsigned')) {
            return;
        }

        try {
            if ($version->getSignatureFile() === null) {
                throw new RuntimeException('Signature file not available');
            }

            $signatureUrl = $this->getBaseUri() . '/' . $version->getSignatureFile();
            $signature    = $this->downloader->downloadFile($signatureUrl, '', true);
        } catch (RuntimeException $exception) {
            $this->cleanup($downloadedPhar);
            throw new RuntimeException('Unable to download signature file', 1, $exception);
        }

        $signatureVerifier = $this->createSignatureVerifier();
        $result            = $signatureVerifier->verify((string) file_get_contents($downloadedPhar), $signature);

        if (!$result->isValid()) {
            $this->cleanup($downloadedPhar);

            throw new RuntimeException('Signature verification failed.');
        }
    }

    private function createSignatureVerifier(): SignatureVerifier
    {
        $gnupgPath = $this->phpcqPath . '/gnupg';
        if (!is_dir($gnupgPath)) {
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

        $composer = new Composer(
            $this->downloader,
            new Filesystem(),
            $this->getWrappedOutput(),
            $this->phpcqPath,
            $this->config->getComposer(),
            $this->findPhpCli()
        );

        if (!$composer->isBinaryAutoDiscovered()) {
            $this->output->writeln('Updating used composer.phar');
            $composer->updateBinary();
        }
    }
}
