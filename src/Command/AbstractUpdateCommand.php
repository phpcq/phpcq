<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\FileDownloader;
use Phpcq\GnuPG\Downloader\KeyDownloader;
use Phpcq\GnuPG\GnuPGFactory;
use Phpcq\GnuPG\Signature\AlwaysStrategy;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Phpcq\GnuPG\Signature\TrustedKeysStrategy;
use Phpcq\GnuPG\Signature\TrustKeyStrategyInterface;
use Phpcq\Platform\PlatformRequirementChecker;
use Phpcq\Repository\JsonRepositoryLoader;
use Phpcq\Repository\RemoteRepository;
use Phpcq\Repository\RepositoryInterface;
use Phpcq\Signature\InteractiveQuestionKeyTrustStrategy;
use Phpcq\Signature\SignatureFileDownloader;
use Phpcq\ToolUpdate\UpdateExecutor;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class AbstractUpdateCommand contains common logic used in the update and install command
 *
 * @psalm-import-type TUpdateTask from \Phpcq\ToolUpdate\UpdateCalculator
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

    /** @var RepositoryInterface|null */
    protected $lockFileRepository;

    /**
     * Only valid when examined from within performUpdate().
     *
     * @var FileDownloader
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $downloader;

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

    protected function doExecute(): int
    {
        $cachePath = $this->input->getOption('cache');
        assert(is_string($cachePath));
        $this->createDirectory($cachePath);

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('Using CACHE: ' . $cachePath);
        }

        $requirementChecker = !$this->input->getOption('ignore-platform-reqs')
            ? PlatformRequirementChecker::create()
            : PlatformRequirementChecker::createAlwaysFulfilling();

        $this->downloader       = new FileDownloader($cachePath, $this->config['auth'] ?? []);
        $this->repositoryLoader = new JsonRepositoryLoader($requirementChecker, $this->downloader, true);
        $lockFile               = $this->getLockFileName();
        if (file_exists($lockFile)) {
            $this->lockFileRepository = new RemoteRepository($lockFile, $this->repositoryLoader);
        }

        $tasks = $this->calculateTasks();
        $this->executeTasks($tasks);

        return 0;
    }

    /** @psalm-return list<TUpdateTask> */
    abstract protected function calculateTasks(): array;

    /** @psalm-param list<TUpdateTask> $tasks */
    protected function executeTasks(array $tasks): void
    {
        $signatureVerifier = new SignatureVerifier(
            (new GnuPGFactory(sys_get_temp_dir()))->create($this->phpcqPath),
            new KeyDownloader(new SignatureFileDownloader($this->downloader)),
            $this->getUntrustedKeyStrategy()
        );

        $executor = new UpdateExecutor(
            $this->downloader,
            $signatureVerifier,
            $this->phpcqPath,
            $this->getWrappedOutput(),
            $this->lockFileRepository,
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
            new TrustedKeysStrategy($this->config['trusted-keys']),
            $this->input,
            $this->output,
            $questionHelper
        );
    }
}
