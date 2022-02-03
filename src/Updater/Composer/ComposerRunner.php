<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Composer;

use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
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

    /** @var Filesystem */
    private $filesystem;

    /** @var list<string> */
    private $command;

    /** @var string */
    private $installedPluginPath;

    /**
     * @param list<string> $command The composer command including the php binary.
     */
    public function __construct(
        OutputInterface $output,
        Filesystem $filesystem,
        string $installedPluginPath,
        array $command
    ) {
        $this->output              = $output;
        $this->filesystem          = $filesystem;
        $this->command             = $command;
        $this->installedPluginPath = $installedPluginPath;
    }

    public function install(PluginVersionInterface $pluginVersion): void
    {
        $this->executeUpdateTask('install', $pluginVersion);
    }

    public function update(PluginVersionInterface $pluginVersion): void
    {
        $this->executeUpdateTask('update', $pluginVersion);
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
        try {
            $process = $this->createProcess(['update', '--dry-run', '--no-progress'], $targetDirectory);
            $process->mustRun();
            $output  = $process->getOutput() ?: $process->getErrorOutput();
        } catch (ProcessFailedException $exception) {
            return true;
        }

        return strpos($output, 'Nothing to install, update or remove') === false;
    }

    private function executeUpdateTask(string $command, PluginVersionInterface $pluginVersion): void
    {
        $targetDirectory = $this->getTargetDirectory($pluginVersion);
        if ($this->clearIfComposerNotRequired($targetDirectory, $pluginVersion)) {
            return;
        }

        $lockFile = $targetDirectory . '/composer.lock';
        $this->dumpComposerJson($targetDirectory . '/composer.json', $pluginVersion);
        $this->execute([$command, '--optimize-autoloader', '--no-progress'], $targetDirectory);
        $this->updateComposerLock($lockFile);
    }

    private function clearIfComposerNotRequired(string $targetDirectory, PluginVersionInterface $pluginVersion): bool
    {
        if (count($pluginVersion->getRequirements()->getComposerRequirements()) > 0) {
            return false;
        }

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

    private function updateComposerLock(string $lockFile): void
    {
        if ($this->filesystem->exists($lockFile)) {
            $lockData = file_get_contents($lockFile);
            $this->dumpComposerLock($lockFile, $lockData);
        }
    }

    private function execute(array $command, string $targetDirectory): void
    {
        try {
            $process = $this->createProcess($command, $targetDirectory);
            $process->mustRun();

            $this->output->write($process->getOutput(), OutputInterface::VERBOSITY_VERBOSE);
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

    private function createProcess(array $command, string $targetDirectory): Process
    {
        return new Process(array_merge($this->command, $command), $targetDirectory, null, null, null);
    }

    private function getTargetDirectory(PluginVersionInterface $pluginVersion): string
    {
        return $this->installedPluginPath . '/' . $pluginVersion->getName();
    }

    public function getComposerLock(PluginVersionInterface $pluginVersion): ?string
    {
        $targetDirectory = $this->getTargetDirectory($pluginVersion);
        $lockFile        = $targetDirectory . '/composer.lock';

        if ($this->filesystem->exists($lockFile)) {
            return file_get_contents($lockFile);
        }

        return null;
    }
}
