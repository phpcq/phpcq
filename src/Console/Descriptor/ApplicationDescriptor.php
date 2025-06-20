<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Descriptor;

use Phpcq\Runner\Console\Definition\ApplicationDefinition;
use Phpcq\Runner\Console\Definition\ArgumentDefinition;
use Symfony\Component\Console\Output\OutputInterface;

final class ApplicationDescriptor extends AbstractDescriptor
{
    private readonly OptionsDescriptor $optionDescriptor;

    public function __construct(OutputInterface $output)
    {
        parent::__construct($output);

        $this->optionDescriptor = new OptionsDescriptor($output);
    }

    public function describe(ApplicationDefinition $application): void
    {
        $this->headline('Description:');
        $this->writeln('  ' . $application->getDescription(), '');
        $this->describeUsage($application);

        $maxlength = 0;
        $groups = [
            [
                'headline' => 'Options:',
                'items' => $this->optionDescriptor->describeOptions($application->getOptions(), $maxlength),
            ],
            [
                'headline' => 'Arguments:',
                'items' => $this->listDefinitions($application->getArguments(), $maxlength)
            ],
            [
                'headline' => 'Commands:',
                'items' => $this->listDefinitions($application->getCommands(), $maxlength)
            ],
        ];

        $this->renderGroups($groups, $maxlength);
    }

    private function describeUsage(ApplicationDefinition $application): void
    {
        $this->headline('Usage:');
        $this->write('  ' . $application->getName());
        if ($application->getOptions()) {
            $this->write(' [options]');
        }

        $this->describeArgumentsUsage($application->getArguments());

        if ($application->getCommands()) {
            $this->write(' [commands]');
        }

        $this->writeln('');
    }

    /** @param array<array-key,ArgumentDefinition> $arguments */
    private function describeArgumentsUsage(array $arguments): void
    {
        foreach ($arguments as $argument) {
            $name = '<' . $argument->getName() . '>';
            if ($argument->isArray()) {
                $name .= '...';
            }

            if (!$argument->isRequired()) {
                $name = '[' . $name . ']';
            }

            $this->write(' ', $name);
        }
    }
}
