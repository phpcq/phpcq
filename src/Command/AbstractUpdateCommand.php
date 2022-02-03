<?php

declare(strict_types=1);

namespace Phpcq\Runner\Command;

use Phpcq\Runner\Composer\ComposerInstaller;
use Phpcq\Runner\Composer\ComposerRunner;
use Phpcq\Runner\Downloader\DownloaderInterface;
use Phpcq\Runner\Downloader\FileDownloader;
use Phpcq\GnuPG\Downloader\KeyDownloader;
use Phpcq\GnuPG\GnuPGFactory;
use Phpcq\GnuPG\Signature\AlwaysStrategy;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\GnuPG\Signature\TrustedKeysStrategy;
use Phpcq\GnuPG\Signature\TrustKeyStrategyInterface;
use Phpcq\Runner\Platform\PlatformRequirementChecker;
use Phpcq\Runner\Repository\DownloadingJsonFileLoader;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\InstalledRepositoryLoader;
use Phpcq\Runner\Repository\JsonRepositoryLoader;
use Phpcq\Runner\Signature\InteractiveQuestionKeyTrustStrategy;
use Phpcq\Runner\Signature\SignatureFileDownloader;
use Phpcq\Runner\Updater\UpdateExecutor;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use function array_filter;
use function file_exists;
use function getcwd;
use function is_dir;
use function mkdir;

/**
 * Class AbstractUpdateCommand contains common logic used in the update and install command
 *
 * @psalm-import-type TPluginTask from \Phpcq\Runner\Updater\UpdateCalculator
 */
abstract class AbstractUpdateCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    /**
     * Only valid when examined from within performUpdate().
     *
     * @var string
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $cachePath;

    /**
     * Only valid when examined from within performUpdate().
     *
     * @var JsonRepositoryLoader
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $repositoryLoader;

    /** @var InstalledRepository|null */
    protected $lockFileRepository;

    /**
     * Only valid when examined from within performUpdate().
     *
     * @var DownloaderInterface
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $downloader;

    /**
     * Only valid when examined from within performUpdate().
     *
     * @var ComposerRunner
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $composer;

    protected function configure(): void
    {
        $this->addOption(
            'cache',
            'x',
            InputOption::VALUE_REQUIRED,
            'Path to the phpcq cache directory',
            (getenv('HOME') ?: sys_get_temp_dir()) . '/.cache/phpcq'
        );

        $this->addOption(
            'trust-keys',
            'k',
            InputOption::VALUE_NONE,
            'Add all keys to trusted key storage'
        );

        parent::configure();
    }

    protected function prepare(InputInterface $input): void
    {
        parent::prepare($input);

        $cachePath = $this->input->getOption('cache');
        assert(is_string($cachePath));
        $this->createDirectory($cachePath);

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('Using CACHE: ' . $cachePath);
        }

        $authConfig       = $this->config->getAuth();
        $this->downloader = new FileDownloader($cachePath, $authConfig);
        $lockFile         = $this->getLockFileName();
        if (file_exists($lockFile)) {
            $this->lockFileRepository = (new InstalledRepositoryLoader())->loadFile($lockFile);
        }

        $installer = new ComposerInstaller(
            $this->downloader,
            $this->output,
            $this->phpcqPath,
            $this->config->getComposer(),
            $this->findPhpCli()
        );
        $this->composer = new ComposerRunner(
            $this->output,
            $this->getPluginPath(),
            $installer->install(),
            $this->lockFileRepository
        );
    }

    protected function doExecute(): int
    {
        $requirementChecker = !$this->input->getOption('ignore-platform-reqs')
            ? PlatformRequirementChecker::create()
            : PlatformRequirementChecker::createAlwaysFulfilling();

        $this->repositoryLoader = new JsonRepositoryLoader(
            $requirementChecker,
            new DownloadingJsonFileLoader($this->downloader, true)
        );

        $tasks   = $this->calculateTasks();
        $changes = array_filter(
            $tasks,
            static function ($task) {
                if ($task['type'] !== 'keep') {
                    return true;
                }

                if (isset($task['composer']) && $task['composer']) {
                    return true;
                }

                if (isset($task['tasks'])) {
                    foreach ($task['tasks'] as $subTask) {
                        if ($subTask['type'] !== 'keep') {
                            return true;
                        }
                    }
                }

                return false;
            }
        );
        if (count($changes) === 0) {
            $this->output->writeln('Nothing to install.');
            return 0;
        }

        $this->executeTasks($tasks);

        return 0;
    }

    /** @psalm-return list<TPluginTask> */
    abstract protected function calculateTasks(): array;

    /** @psalm-param list<TPluginTask> $tasks */
    protected function executeTasks(array $tasks): void
    {
        $gnupgPath = $this->phpcqPath . '/gnupg';
        if (! is_dir($gnupgPath)) {
            mkdir($gnupgPath, 0777, true);
        }
        $signatureVerifier = new SignatureVerifier(
            (new GnuPGFactory(sys_get_temp_dir()))->create($gnupgPath),
            new KeyDownloader(new SignatureFileDownloader($this->downloader, $this->output)),
            $this->getUntrustedKeyStrategy()
        );

        $executor = new UpdateExecutor(
            $this->downloader,
            $signatureVerifier,
            $this->getPluginPath(),
            $this->getWrappedOutput(),
            $this->composer
        );
        $executor->execute($tasks);
    }

    protected function getLockFileName(): string
    {
        return getcwd() . '/.phpcq.lock';
    }

    protected function getUntrustedKeyStrategy(): TrustKeyStrategyInterface
    {
        if ($this->input->getOption('trust-keys')) {
            return AlwaysStrategy::TRUST();
        }

        $questionHelper = $this->getHelper('question');
        assert($questionHelper instanceof QuestionHelper);

        return new InteractiveQuestionKeyTrustStrategy(
            new TrustedKeysStrategy($this->config->getTrustedKeys()),
            $this->input,
            $this->output,
            $questionHelper
        );
    }
}
