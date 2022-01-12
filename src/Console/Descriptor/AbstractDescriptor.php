<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Descriptor;

use Phpcq\Runner\Console\Definition\AbstractDefinition;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-type TGroupItem = array{term:string, description:string}
 * @psalm-type TGroup = array{headline: string, items: list<TGroupItem>}
 */
abstract class AbstractDescriptor
{
    /** @var OutputInterface */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    protected function write(string ...$messages): void
    {
        $this->output->write($messages);
    }

    protected function writeln(string ...$messages): void
    {
        if ($messages === []) {
            $this->output->writeln(' ');
        } else {
            $this->output->writeln($messages);
        }
    }

    /**
     * @param array<array-key,AbstractDefinition> $definitions
     *
     * @return list<TGroupItem>
     */
    protected function listDefinitions(array $definitions, int &$maxlength): array
    {
        $items = [];

        foreach ($definitions as $definition) {
            $maxlength = max($maxlength, Helper::width($definition->getName()));
            $items[] = [
                'term' => $definition->getName(),
                'description' => $definition->getDescription()
            ];
        }

        return $items;
    }

    /** @param list<TGroup> $groups */
    protected function renderGroups(array $groups, int $maxlength): void
    {
        foreach ($groups as $group) {
            if (count($group['items']) === 0) {
                continue;
            }

            $this->writeln('');
            $this->headline($group['headline']);

            foreach ($group['items'] as $item) {
                $this->write('  <info>', $item['term'], '</info>');
                $this->write(str_repeat(' ', $maxlength - Helper::width($item['term'])), '  ');
                $this->writeln($item['description']);
            }
        }
    }

    protected function headline(string $headline): void
    {
        $this->write('<comment>', $headline);
        $this->writeln('</comment>');
    }
}
