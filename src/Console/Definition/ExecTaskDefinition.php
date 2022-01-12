<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition;

use Phpcq\Runner\Exception\InvalidArgumentException;

final class ExecTaskDefinition
{
    /** @var array<string, ApplicationDefinition> */
    private $applications = [];

    /**
     * @param ApplicationDefinition[] $applications
     */
    public function __construct(array $applications)
    {
        foreach ($applications as $application) {
            $this->applications[$application->getName()] = $application;
        }

        ksort($this->applications);
    }

    /** @return array<string, ApplicationDefinition> */
    public function getApplications(): array
    {
        return $this->applications;
    }

    public function getApplication(string $name): ApplicationDefinition
    {
        if (!array_key_exists($name, $this->applications)) {
            throw new InvalidArgumentException('Application "' . $name . '" not found');
        }

        return $this->applications[$name];
    }
}
