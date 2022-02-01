<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition;

use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleApplicationBuilderInterface;
use Phpcq\PluginApi\Version10\Definition\ExecTaskDefinitionBuilderInterface;
use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\ExecPluginInterface;
use Phpcq\Runner\Config\ProjectConfiguration;
use Phpcq\Runner\Console\Definition\Builder\ConsoleApplicationBuilder;
use Phpcq\Runner\Environment;
use Phpcq\Runner\Plugin\PluginRegistry;
use Phpcq\Runner\Repository\InstalledRepository;
use Phpcq\Runner\Task\SingleProcessTaskFactory;
use Phpcq\Runner\Task\TaskFactory;

final class ExecTaskDefinitionBuilder implements ExecTaskDefinitionBuilderInterface
{
    /** @var ProjectConfiguration */
    private $projectConfig;

    /** @var PluginRegistry */
    private $plugins;

    /** @var InstalledRepository */
    private $installed;

    /** @var array{string, list<string>} */
    private $phpCli;

    /** @var string */
    private $tempDirectory;

    /** @var array<string,ConsoleApplicationBuilder>  */
    private $applications = [];

    /** @var string|null */
    private $currentPluginName;

    /**
     * @param array{string, list<string>} $phpCli
     */
    public function __construct(
        ProjectConfiguration $projectConfig,
        PluginRegistry $plugins,
        InstalledRepository $installed,
        array $phpCli,
        string $tempDirectory
    ) {
        $this->projectConfig = $projectConfig;
        $this->plugins       = $plugins;
        $this->installed     = $installed;
        $this->phpCli        = $phpCli;
        $this->tempDirectory = $tempDirectory;
    }

    public function describeApplication(
        string $description,
        ?string $applicationName = null
    ): ConsoleApplicationBuilderInterface {
        $name = $this->currentPluginName;
        assert(is_string($name));

        if ($applicationName) {
            $name .= ':' . $applicationName;
        }

        if (array_key_exists($name, $this->applications)) {
            throw new RuntimeException('Application named "' . $name . '" already defined');
        }

        return $this->applications[$name] = new ConsoleApplicationBuilder($name, $description);
    }

    public function build(): ExecTaskDefinition
    {
        /** @var array<string,ConsoleApplicationBuilder> */
        $this->applications = [];

        foreach ($this->plugins->getByType(ExecPluginInterface::class) as $plugin) {
            $this->describePluginApplications($plugin);
        }

        $applications = [];
        foreach ($this->applications as $application) {
            $applications[] = $application->build();
        }

        return new ExecTaskDefinition($applications);
    }

    private function describePluginApplications(ExecPluginInterface $plugin): void
    {
        $this->currentPluginName = $plugin->getName();
        $plugin->describeExecTask($this, $this->createEnvironment($plugin));
    }

    private function createEnvironment(ExecPluginInterface $plugin): Environment
    {
        return new Environment(
            $this->projectConfig,
            new SingleProcessTaskFactory(new TaskFactory(
                $plugin->getName(),
                $this->installed->getPlugin($plugin->getName()),
                ...$this->phpCli
            )),
            $this->tempDirectory,
            1
        );
    }
}
