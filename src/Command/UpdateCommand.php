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
use Phpcq\Repository\RepositoryFactory;
use Phpcq\Signature\InteractiveQuestionKeyTrustStrategy;
use Phpcq\Signature\SignatureFileDownloader;
use Phpcq\ToolUpdate\UpdateCalculator;
use Phpcq\ToolUpdate\UpdateExecutor;
use Symfony\Component\Console\Input\InputOption;

use function assert;
use function is_string;
use function sys_get_temp_dir;

final class UpdateCommand extends AbstractCommand
{
    use InstalledRepositoryLoadingCommandTrait;

    protected function configure(): void
    {
        $this->setName('update')->setDescription('Update the phpcq installation');
        $this->addOption(
            'cache',
            'x',
            InputOption::VALUE_REQUIRED,
            'Path to the phpcq cache directory',
            (getenv('HOME') ?: sys_get_temp_dir()) . '/.cache/phpcq'
        );
        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Dry run'
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

        $downloader       = new FileDownloader($cachePath, $this->config['auth'] ?? []);
        $repositoryLoader = new JsonRepositoryLoader($requirementChecker, $downloader, true);
        $factory          = new RepositoryFactory($repositoryLoader);
        // Download repositories
        $pool = $factory->buildPool($this->config['repositories'] ?? []);

        $consoleOutput = $this->getWrappedOutput();

        $calculator = new UpdateCalculator($this->getInstalledRepository(false), $pool, $consoleOutput);
        $tasks = $calculator->calculate($this->config['tools']);

        if ($this->input->getOption('dry-run')) {
            foreach ($tasks as $task) {
                $this->output->writeln($task['message']);
            }
            return 0;
        }

        $signatureVerifier = new SignatureVerifier(
            (new GnuPGFactory(sys_get_temp_dir()))->create($this->phpcqPath),
            new KeyDownloader(new SignatureFileDownloader($downloader)),
            $this->getUntrustedKeyStrategy()
        );

        $executor = new UpdateExecutor($downloader, $signatureVerifier, $this->phpcqPath, $consoleOutput);
        $executor->execute($tasks);

        return 0;
    }

    protected function getUntrustedKeyStrategy(): TrustKeyStrategyInterface
    {
        if ($this->input->getOption('trust-keys')) {
            return AlwaysStrategy::TRUST();
        }

        return new InteractiveQuestionKeyTrustStrategy(
            new TrustedKeysStrategy($this->config['trusted-keys']),
            $this->input,
            $this->output,
            $this->getHelper('question')
        );
    }
}
