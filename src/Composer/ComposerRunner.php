<?php

declare(strict_types=1);

namespace Phpcq\Runner\Composer;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use function array_merge;
use function assert;
use function count;
use function file_get_contents;
use function file_put_contents;
use function json_encode;
use function strpos;

use const JSON_FORCE_OBJECT;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

class ComposerRunner
{
    private const JSON_ENCODE_OPTIONS = JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT;

    /** @var OutputInterface */
    private $output;

    /** @var list<string> */
    private $command;

    /** @var Filesystem */
    private $filesystem;

    /** @var InstalledRepository|null */
    private $lockFileRepository;

    /** @var string */
    private $installedPluginPath;

    /**
     * @param list<string> $command The composer command including the php binary.
     */
    public function __construct(
        OutputInterface $output,
        string $installedPluginPath,
        array $command,
        ?InstalledRepository $lockFileRepository
    ) {
        $this->output              = $output;
        $this->command             = $command;
        $this->installedPluginPath = $installedPluginPath;
        $this->lockFileRepository  = $lockFileRepository;
        $this->filesystem          = new Filesystem();
    }

    public function install(InstalledPlugin $plugin): void
    {
        $this->executeUpdateTask('install', $plugin);
    }

    public function update(InstalledPlugin $plugin): void
    {
        $this->executeUpdateTask('update', $plugin);
    }

    public function isUpdateRequired(PluginVersionInterface $pluginVersion): bool
    {
        $targetDirectory    = $this->getTargetDirectory($pluginVersion);
        $composerJsonExists = $this->filesystem->exists($targetDirectory . '/composer.json');

        // Composer.json exists but no requirements
        if (count($pluginVersion->getRequirements()->getComposerRequirements()) === 0) {
            return $composerJsonExists;
        }

        // No composer.json exist, update required
        if (!$composerJsonExists) {
            return true;
        }

        // Try composer update dry-run
        $process = $this->createProcess(['update', '--dry-run', '--no-progress'], $targetDirectory);
        $process->mustRun();
        $output  = $process->getOutput() ?: $process->getErrorOutput();

        return strpos($output, 'Nothing to install, update or remove') === false;
    }

    private function executeUpdateTask(string $command, InstalledPlugin $plugin): void
    {
        $targetDirectory = $this->getTargetDirectory($plugin->getPluginVersion());
        if ($this->clearIfComposerNotRequired($targetDirectory, $plugin)) {
            return;
        }

        $lockFile = $targetDirectory . '/composer.lock';
        if ($this->lockFileRepository && $this->lockFileRepository->hasPlugin($plugin->getName())) {
            $this->dumpComposerLock(
                $lockFile,
                $this->lockFileRepository->getPlugin($plugin->getName())->getComposerLock()
            );
        }

        $this->dumpComposerJson($targetDirectory . '/composer.json', $plugin->getPluginVersion());
        $this->execute([$command, '--optimize-autoloader', '--no-progress'], $targetDirectory);
        $this->updateComposerLock($lockFile, $plugin);
    }

    private function clearIfComposerNotRequired(string $targetDirectory, InstalledPlugin $plugin): bool
    {
        if (count($plugin->getPluginVersion()->getRequirements()->getComposerRequirements()) > 0) {
            return false;
        }

        $plugin->updateComposerLock(null);
        $this->filesystem->remove(
            [$targetDirectory . '/vendor', $targetDirectory . '/composer.json', $targetDirectory . '/composer.lock']
        );

        return true;
    }

    private function dumpComposerJson(string $composerFile, PluginVersionInterface $pluginVersion): void
    {
        $data = [
            'type'    => 'project',
            'require' => [],
            'config'  => [
                'allow-plugins' => true,
            ]
        ];

        // TODO: Handle auth configuration

        foreach ($pluginVersion->getRequirements()->getComposerRequirements() as $requirement) {
            $data['require'][$requirement->getName()] = $requirement->getConstraint();
        }

        file_put_contents($composerFile, json_encode($data, self::JSON_ENCODE_OPTIONS));
    }

    private function dumpComposerLock(string $lockFile, ?string $lockData): void
    {
        if ($lockData) {
            $this->filesystem->dumpFile($lockFile, $lockData);
        } elseif ($this->filesystem->exists($lockFile)) {
            $this->filesystem->remove($lockFile);
        }
    }

    private function updateComposerLock(string $lockFile, InstalledPlugin $plugin): void
    {
        if ($this->filesystem->exists($lockFile)) {
            $lockData = file_get_contents($lockFile);
            $this->dumpComposerLock($lockFile, $lockData);
            $plugin->updateComposerLock($lockData);
        } else {
            $plugin->updateComposerLock(null);
        }
    }

    private function execute(array $command, string $targetDirectory): void
    {
        try {
            $process = $this->createProcess($command, $targetDirectory);
            $process->mustRun();

            $output = $process->getOutput();
            if ($output) {
                $this->output->write($output, false, OutputInterface::VERBOSITY_VERBOSE);
            }
        } catch (ProcessFailedException $exception) {
            /** @psalm-suppress MixedAssignment */
            $process = $exception->getProcess();
            assert($process instanceof Process);
            $output = $process->getOutput();
            if ($output) {
                $this->output->write($output, false, OutputInterface::VERBOSITY_VERBOSE);
            }

            throw $exception;
        }
    }

    private function createProcess(array $command, string $targetDirectory): Process
    {
        return new Process(array_merge($this->command, $command), $targetDirectory, null, null, null);
    }

    private function getTargetDirectory(PluginVersionInterface $pluginVersion): string
    {
        return $this->installedPluginPath . '/' . $pluginVersion->getName();
    }
}
