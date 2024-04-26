<?php

declare(strict_types=1);

namespace Phpcq\Runner\Updater\Task\Composer;

use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirementList;
use Phpcq\Runner\Updater\Task\TaskInterface;
use Phpcq\Runner\Updater\UpdateContext;

use function json_encode;

use const JSON_FORCE_OBJECT;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

abstract class AbstractComposerTask implements TaskInterface
{
    protected const JSON_ENCODE_OPTIONS = JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT;

    protected PluginVersionInterface $pluginVersion;

    protected ?VersionRequirementList $requirements;

    /** @param ?VersionRequirementList $requirements */
    public function __construct(PluginVersionInterface $pluginVersion, ?VersionRequirementList $requirements = null)
    {
        $this->pluginVersion = $pluginVersion;
        $this->requirements  = $requirements;
    }

    public function getPluginName(): string
    {
        return $this->pluginVersion->getName();
    }

    protected function updateComposerLock(UpdateContext $context): void
    {
        $context->lockRepository
            ->getPlugin($this->getPluginName())
            ->updateComposerLock($this->getComposerLock($context));
    }

    protected function dumpComposerJson(UpdateContext $context): void
    {
        $data = [
            'type'    => 'project',
            'require' => [],
            'config'  => [
                'allow-plugins' => true,
            ],
        ];

        // TODO: Handle auth configuration

        $composerFile = $this->locatePath($context, 'composer.json');

        if ($this->requirements) {
            foreach ($this->requirements as $requirement) {
                $name                   = $requirement->getName();
                $data['require'][$name] = $requirement->getConstraint();
            }
        }

        file_put_contents($composerFile, json_encode($data, self::JSON_ENCODE_OPTIONS));
    }

    protected function getComposerLock(UpdateContext $context): ?string
    {
        $lockFile = $this->locatePath($context, 'composer.lock');

        if ($context->filesystem->exists($lockFile)) {
            return file_get_contents($lockFile);
        }

        return null;
    }

    protected function clearIfComposerNotRequired(UpdateContext $context): bool
    {
        if (count($this->pluginVersion->getRequirements()->getComposerRequirements()) > 0) {
            return false;
        }

        $context->filesystem->remove(
            [
                $this->locatePath($context, 'vendor'),
                $this->locatePath($context, 'composer.json'),
                $this->locatePath($context, 'composer.lock'),
            ],
        );

        return true;
    }

    protected function getTargetDirectory(UpdateContext $context): string
    {
        return $context->installedPluginPath . '/' . $this->getPluginName();
    }

    protected function locatePath(UpdateContext $context, string $fileName): string
    {
        return $this->getTargetDirectory($context) . '/' . $fileName;
    }
}
