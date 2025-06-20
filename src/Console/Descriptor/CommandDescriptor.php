<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Descriptor;

use Phpcq\Runner\Console\Definition\CommandDefinition;
use Symfony\Component\Console\Output\OutputInterface;

final class CommandDescriptor extends AbstractDescriptor
{
    private readonly OptionsDescriptor $optionDescriptor;

    public function __construct(OutputInterface $output)
    {
        parent::__construct($output);

        $this->optionDescriptor = new OptionsDescriptor($output);
    }

    public function describe(string $applicationName, CommandDefinition $command): void
    {
        $this->write($applicationName, ' ', $command->getName());
        $this->writeln('', '', $command->getDescription());
        $this->describeUsage($applicationName, $command);

        $maxlength = 0;
        $groups    = [
            [
                'headline' => 'Options:',
                'items'    => $this->optionDescriptor->describeOptions($command->getOptions(), $maxlength),
            ],
            [
                'headline' => 'Arguments:',
                'items'    => $this->listDefinitions($command->getArguments(), $maxlength),
            ],
        ];

        $this->renderGroups($groups, $maxlength);
    }

    private function describeUsage(string $applicationName, CommandDefinition $command): void
    {
        $this->writeln('', '<comment>Usage:</comment>');
        $this->write('  ', $applicationName, ' ', $command->getName());
        if ($command->getOptions()) {
            $this->write(' [options]');
        }

        if ($command->getArguments()) {
            $this->write(' [arguments]');
        }

        $this->writeln();
    }
}
