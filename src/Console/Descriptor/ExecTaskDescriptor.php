<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Descriptor;

use Phpcq\Runner\Console\Definition\ExecTaskDefinition;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputDefinition;

final class ExecTaskDescriptor extends AbstractDescriptor
{
    public function describe(ExecTaskDefinition $definition, InputDefinition $inputDefinition): void
    {
        $maxlength = $this->determineMaxlength($inputDefinition);

        $this->renderGroups(
            [
                [
                    'headline' => 'Applications:',
                    'items' => $this->listDefinitions($definition->getApplications(), $maxlength)
                ]
            ],
            $maxlength
        );
    }

    private function determineMaxlength(InputDefinition $inputDefinition): int
    {
        $maxlength = 0;

        foreach ($inputDefinition->getArguments() as $argument) {
            $maxlength = max($maxlength, Helper::width($argument->getName()));
        }

        foreach ($inputDefinition->getOptions() as $option) {
            // Assume that alway any option has a shortcut, so that 6 additional characters are used, e.g. "-s, --"
            $maxlength = max($maxlength, Helper::width($option->getName()) + 6);
        }

        return $maxlength;
    }
}
