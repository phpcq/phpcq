<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Http\Adapter\Guzzle6\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Message\UriFactory\GuzzleUriFactory;
use Phpcq\FileDownloader;
use Phpcq\GnuPG\GnuPGFactory;
use Phpcq\GnuPG\KeyDownloader;
use Phpcq\GnuPG\SignatureVerifier;
use Phpcq\Platform\PlatformRequirementChecker;
use Phpcq\Repository\JsonRepositoryLoader;
use Phpcq\Repository\RepositoryFactory;
use Phpcq\ToolUpdate\UpdateCalculator;
use Phpcq\ToolUpdate\UpdateExecutor;
use Symfony\Component\Console\Input\InputOption;
use function assert;
use function is_string;

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

        $signatureVerifier = $this->createSignatureVerifier($downloader);
        $executor = new UpdateExecutor($downloader, $signatureVerifier, $this->phpcqPath, $consoleOutput);
        $executor->execute($tasks);

        return 0;
    }

    protected function createSignatureVerifier(FileDownloader $downloader) : SignatureVerifier
    {
        return new SignatureVerifier(
            (new GnuPGFactory($this->phpcqPath))->create(),
            new KeyDownloader($downloader),
            $this->config['trusted-keys']
        );
    }
}
