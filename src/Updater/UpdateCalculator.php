<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater;

use Composer\Semver\Semver;
use Generator;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\RepositoryDefinition\AbstractHash;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Composer;
use Phpcq\Runner\Repository\BuiltInPlugin;
use Phpcq\Runner\Repository\InstalledPlugin;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Repository\Repository;
use Phpcq\Runner\Repository\RepositoryInterface;
use Phpcq\Runner\Resolver\ResolverInterface;
use Phpcq\Runner\Updater\Task\Composer\ComposerInstallTask;
use Phpcq\Runner\Updater\Task\Composer\ComposerUpdateTask;
use Phpcq\Runner\Updater\Task\Composer\RemoveComposerDependenciesTask;
use Phpcq\Runner\Updater\Task\Plugin\InstallPluginTask;
use Phpcq\Runner\Updater\Task\Plugin\KeepPluginTask;
use Phpcq\Runner\Updater\Task\Plugin\RemovePluginTask;
use Phpcq\Runner\Updater\Task\Plugin\UpgradePluginTask;
use Phpcq\Runner\Updater\Task\Tool\InstallToolTask;
use Phpcq\Runner\Updater\Task\Tool\KeepToolTask;
use Phpcq\Runner\Updater\Task\Tool\RemoveToolTask;
use Phpcq\Runner\Updater\Task\Tool\UpgradeToolTask;
use Phpcq\Runner\Updater\Task\TaskInterface;

use function count;
use function file_exists;
use function is_dir;

/**
 * @psalm-import-type TPlugin from \Phpcq\Runner\Config\PhpcqConfiguration
 * @psalm-import-type TOutputVerbosity from \Phpcq\PluginApi\Version10\Output\OutputInterface
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class UpdateCalculator
{
    /**
     * @var InstalledRepository
     */
    private $installed;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var int
     * @psalm-var TOutputVerbosity
     */
    private $verbosity;

    /**  @psalm-param TOutputVerbosity $verbosity */
    public function __construct(
        InstalledRepository $installed,
        ResolverInterface $resolver,
        Composer $composer,
        OutputInterface $output,
        int $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE
    ) {
        $this->installed = $installed;
        $this->composer  = $composer;
        $this->output    = $output;
        $this->resolver  = $resolver;
        $this->verbosity = $verbosity;
    }

    /**
     * @param bool $forceReinstall Intended to use if no lock file exists. Php file plugin required for all tools.
     *
     * @psalm-param array<string,TPlugin> $plugins
     * @psalm-return list<TaskInterface>
     */
    public function calculate(array $plugins, bool $forceReinstall = false): array
    {
        return $this->calculateTasksToExecute($this->calculateDesiredPlugins($plugins), $plugins, $forceReinstall);
    }

    /**
     * @psalm-param array<string,TPlugin> $plugins
     */
    private function calculateDesiredPlugins(array $plugins): RepositoryInterface
    {
        $desired = new Repository();
        foreach ($plugins as $pluginName => $plugin) {
            $pluginVersion = $this->resolver->resolvePluginVersion($pluginName, $plugin['version']);
            $desired->addPluginVersion($pluginVersion);
            $this->output->writeln(
                'Want ' . $pluginVersion->getName() . ' in version ' . $pluginVersion->getVersion(),
                OutputInterface::VERBOSITY_DEBUG
            );
        }

        return $desired;
    }

    /**
     * @param bool $forceReinstall Intended to use if no lock file exists. Php file plugin required for all tools.
     *
     * @psalm-param array<string,TPlugin> $plugins
     *
     * @return array[]
     * @psalm-return list<TaskInterface>
     */
    protected function calculateTasksToExecute(
        RepositoryInterface $desired,
        array $plugins,
        bool $forceReinstall = false
    ): array {
        // Determine diff to current installation.
        $tasks = [];
        foreach ($desired->iteratePluginVersions() as $pluginVersion) {
            $name = $pluginVersion->getName();

            // Not installed yet => install.
            if (!$this->installed->hasPlugin($name)) {
                foreach ($this->calculateInstallTasks($pluginVersion, $plugins, $forceReinstall) as $task) {
                    $this->output->writeln($task->getPurposeDescription(), $this->verbosity);
                    $tasks[] = $task;
                }
                continue;
            }

            // Installed in another version => upgrade.
            $installed = $this->installed->getPlugin($name);
            if ($forceReinstall || $this->isPluginUpgradeRequired($pluginVersion)) {
                foreach (
                    $this->calculateUpgradeTasks(
                        $pluginVersion,
                        $installed->getPluginVersion(),
                        $plugins,
                        $forceReinstall
                    ) as $task
                ) {
                    $this->output->writeln($task->getPurposeDescription(), $this->verbosity);
                    $tasks[] = $task;
                }

                continue;
            }

            foreach (
                $this->calculateKeepTasks(
                    $pluginVersion,
                    $installed->getPluginVersion(),
                    $plugins,
                    $forceReinstall
                ) as $task
            ) {
                $this->output->writeln($task->getPurposeDescription(), $this->verbosity);
                $tasks[] = $task;
            }
        }
        // Determine uninstalls now.
        foreach ($this->calculateDeleteTasks($desired) as $task) {
            $this->output->writeln($task->getPurposeDescription(), $this->verbosity);
            $tasks[] = $task;
        }

        return $tasks;
    }

    private function isPluginUpgradeRequired(PluginVersionInterface $desired): bool
    {
        if (!$this->installed->hasPlugin($desired->getName())) {
            return true;
        }

        $installed = $this->installed->getPlugin($desired->getName());
        if (Semver::satisfies($installed->getPluginVersion()->getVersion(), $desired->getVersion())) {
            // FIXME: Remove usage of hasHashChanged
            /** @psalm-suppress DeprecatedMethod */
            return $this->hasHashChanged($desired->getHash(), $installed->getPluginVersion()->getHash());
        }

        return true;
    }

    /** @deprecated Remove when we have versioned plugins */
    private function hasHashChanged(?AbstractHash $desired, ?AbstractHash $installed): bool
    {
        // No hash given. We can't verify changes, force reinstall
        if (null === $desired) {
            return true;
        }

        // No hash given for installed tool but new version has a hash, forche reinstall
        if (null === $installed) {
            return true;
        }

        return !$desired->equals($installed);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @psalm-param array<string,TPlugin> $plugins
     *
     * @psalm-return Generator<TaskInterface>
     */
    private function calculateToolTasks(
        PluginVersionInterface $desired,
        array $plugins,
        bool $forceReinstall
    ): Generator {
        $config     = $plugins[$desired->getName()] ?? [];
        $pluginName = $desired->getName();
        $plugin     = $this->installed->hasPlugin($pluginName) ? $this->installed->getPlugin($pluginName) : null;

        foreach ($desired->getRequirements()->getToolRequirements() as $toolRequirement) {
            // FIXME: Check if configured requirement is within the tool requirement
            $requirementName = $toolRequirement->getName();
            $constraint = $config['requirements'][$requirementName]['version']
                ?? $toolRequirement->getConstraint();
            $tool       = $this->resolver->resolveToolVersion($pluginName, $requirementName, $constraint);
            $toolName   = $tool->getName();

            if (!$plugin || !$plugin->hasTool($requirementName)) {
                yield new InstallToolTask(
                    $desired,
                    $tool,
                    $config['requirements'][$toolName]['signed'] ?? true
                );

                continue;
            }
            // Installed in another version => upgrade.
            $installed = $plugin->getTool($toolName);
            if ($forceReinstall || $this->isToolUpgradeRequired($plugin, $tool)) {
                yield new UpgradeToolTask(
                    $desired,
                    $tool,
                    $installed,
                    $config['requirements'][$toolName]['signed'] ?? true
                );
                continue;
            }
            // Keep the tool otherwise.
            yield new KeepToolTask($desired, $tool, $installed);
        }

        if ($plugin) {
            foreach ($plugin->iterateTools() as $tool) {
                if (!$desired->getRequirements()->getToolRequirements()->has($tool->getName())) {
                    yield new RemoveToolTask($desired, $tool);
                }
            }
        }
    }

    private function isToolUpgradeRequired(InstalledPlugin $plugin, ToolVersionInterface $desired): bool
    {
        $installed = $plugin->getTool($desired->getName());
        if (Semver::satisfies($installed->getVersion(), $desired->getVersion())) {
            // FIXME: Remove usage of hasHashChanged
            /** @psalm-suppress DeprecatedMethod */
            return $desired->getHash() && $this->hasHashChanged($desired->getHash(), $installed->getHash());
        }

        return true;
    }

    /**
     * @psalm-param array<string,TPlugin> $plugins
     *
     * @return Generator<TaskInterface>
     */
    private function calculateInstallTasks(
        PluginVersionInterface $pluginVersion,
        array $plugins,
        bool $forceReinstall
    ): Generator {
        yield new InstallPluginTask(
            $pluginVersion,
            $plugins[$pluginVersion->getName()]['signed'] ?? true
        );

        foreach ($this->calculateToolTasks($pluginVersion, $plugins, $forceReinstall) as $task) {
            yield $task;
        }

        foreach ($this->calculateComposerTasks($pluginVersion) as $task) {
            yield $task;
        }
    }

    /**
     * @psalm-param array<string,TPlugin> $plugins
     *
     * @return Generator<TaskInterface>
     */
    private function calculateUpgradeTasks(
        PluginVersionInterface $pluginVersion,
        PluginVersionInterface $installedVersion,
        array $plugins,
        bool $forceReinstall
    ): Generator {
        yield new UpgradePluginTask(
            $pluginVersion,
            $installedVersion,
            $plugins[$pluginVersion->getName()]['signed'] ?? true
        );

        foreach ($this->calculateToolTasks($pluginVersion, $plugins, $forceReinstall) as $task) {
            yield $task;
        }

        foreach ($this->calculateComposerTasks($pluginVersion, $installedVersion) as $task) {
            yield $task;
        }
    }

    /**
     * @psalm-param array<string,TPlugin> $plugins
     *
     * @return Generator<TaskInterface>
     */
    private function calculateKeepTasks(
        PluginVersionInterface $pluginVersion,
        PluginVersionInterface $installedVersion,
        array $plugins,
        bool $forceReinstall
    ): Generator {
        // Keep the tool otherwise.
        yield new KeepPluginTask($pluginVersion, $installedVersion);

        foreach ($this->calculateToolTasks($pluginVersion, $plugins, $forceReinstall) as $task) {
            yield $task;
        }

        foreach ($this->calculateComposerTasks($pluginVersion, $installedVersion) as $task) {
            yield $task;
        }
    }

    /**
     * @return Generator<TaskInterface>
     */
    private function calculateDeleteTasks(RepositoryInterface $desired): Generator
    {
        foreach ($this->installed->iteratePlugins() as $installedPlugin) {
            if ($installedPlugin instanceof BuiltInPlugin) {
                continue;
            }

            $name = $installedPlugin->getName();
            if (!$desired->hasPluginVersion($name, '*')) {
                yield new RemovePluginTask($installedPlugin->getPluginVersion());
            }
        }
    }

    /**
     * @psalm-return Generator<TaskInterface>
     */
    private function calculateComposerTasks(
        PluginVersionInterface $pluginVersion,
        ?PluginVersionInterface $installedVersion = null
    ): Generator {
        $hasRequirements = count($pluginVersion->getRequirements()->getComposerRequirements()) > 0;
        $isInstalled     = $installedVersion && $this->areComposerDependenciesInstalled(
            dirname($installedVersion->getFilePath())
        );

        if (!$isInstalled) {
            if ($hasRequirements) {
                yield new ComposerInstallTask($pluginVersion);
            }

            return;
        }

        if ($hasRequirements) {
            $targetDirectory = dirname($installedVersion->getFilePath());

            if ($this->composer->isUpdateRequired($targetDirectory)) {
                yield new ComposerUpdateTask($pluginVersion);
            }

            return;
        }

        yield new RemoveComposerDependenciesTask($pluginVersion);
    }

    private function areComposerDependenciesInstalled(string $targetDirectory): bool
    {
        if (file_exists($targetDirectory . '/composer.json')) {
            return true;
        }
        if (file_exists($targetDirectory . '/composer.lock')) {
            return true;
        }

        return is_dir($targetDirectory . '/vendor');
    }
}
