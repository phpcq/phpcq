<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Descriptor;

use Generator;
use Phpcq\Runner\Console\Definition\OptionDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\KeyValueMapOptionValueDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\OptionParamsDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\OptionValueDefinition;
use Phpcq\Runner\Console\Definition\OptionValue\SimpleOptionValueDefinition;
use Symfony\Component\Console\Helper\Helper;

/**
 * @psalm-import-type TGroupItem from AbstractDescriptor
 */
final class OptionsDescriptor extends AbstractDescriptor
{
    /**
     * @param OptionDefinition[] $options
     *
     * @return list<TGroupItem>
     */
    public function describeOptions(array $options, int &$maxlength): array
    {
        $shortcutLength = 0;
        $descriptions   = [];

        foreach ($options as $option) {
            $info        = ($option->isOnlyShortCut() ? ' -' : '--') . $option->getName();
            $short       = $this->describeShortCut($option);
            $description = $option->getDescription();

            foreach ($this->describeOptionValue($option, $info, $description) as $info => $description) {
                $length         = Helper::width($info);
                $shortLength    = Helper::width($short);
                $maxlength      = max($maxlength, $length);
                $shortcutLength = max($shortcutLength, $shortLength);
                $descriptions[] = [
                    'short'       => $short,
                    'shortLength' => $shortLength,
                    'info'        => $info,
                    'infoLength'  => $length,
                    'description' => $description,
                ];
            }
        }

        $items = [];

        foreach ($descriptions as $description) {
            $term = str_repeat(' ', $shortcutLength - $description['shortLength'])
                . $description['short']
                . $description['info'];

            $maxlength = max($maxlength, Helper::width($term));
            $items[] = [
                'term' => $term,
                'description' => $description['description'],
            ];
        }

        return $items;
    }

    private function describeShortCut(OptionDefinition $definition): string
    {
        $short = '';
        if ($definition->isOnlyShortcut()) {
            return '';
        }

        if ($shortcut = $definition->getShortcut()) {
            $short .= '-' . $shortcut;
            $short .= ', ';
        }

        return $short;
    }

    /** @return Generator<string,string> */
    private function describeOptionValue(
        OptionDefinition $option,
        string $info,
        string $description
    ): Generator {
        $value = $option->getOptionValue();

        switch (true) {
            case $value instanceof SimpleOptionValueDefinition:
                yield from $this->describeSimpleOptionValue($option, $value, $info, $description);
                return;

            case $value instanceof KeyValueMapOptionValueDefinition:
                yield from $this->describeKeyValueMapOptionValue($option, $value, $info, $description);
                return;

            case $value instanceof OptionParamsDefinition:
                yield from $this->describeNamedOptionValues($option, $value, $info, $description);
                return;

            case $value instanceof OptionValueDefinition:
                if ($value->isRequired()) {
                    $info .= $option->getValueSeparator() . 'VALUE';
                }

                yield $info => $description;

                break;

            default:
                yield $info => $description;
        }
    }

    /** @param mixed $defaultValue */
    private function describeDefaultValue($defaultValue): string
    {
        return ' <comment>[default: ' . json_encode($defaultValue) . ']</comment>';
    }

    /** @return Generator<string,string> */
    private function describeSimpleOptionValue(
        OptionDefinition $option,
        SimpleOptionValueDefinition $value,
        string $info,
        string $description
    ): Generator {
        $info .= $option->getValueSeparator();
        $info .= strtoupper($value->getValueName() ?: $option->getName());

        if (null !== $value->getDefaultValue()) {
            $description .= $this->describeDefaultValue($value->getDefaultValue());
        }

        yield $info => $description;
    }

    /** @return Generator<string,string> */
    private function describeKeyValueMapOptionValue(
        OptionDefinition $option,
        KeyValueMapOptionValueDefinition $value,
        string $info,
        string $description
    ): Generator {
        $info .= $option->getValueSeparator();
        $info .= 'KEY';

        if (null !== $value->getDefaultValue()) {
            $info .= '[' . $value->getValueSeparator() . 'VALUE]';
            $description .= $this->describeDefaultValue($value->getDefaultValue());
        } else {
            $info .= $value->getValueSeparator() . 'VALUE';
        }

        yield $info => $description;
    }

    /** @return Generator<string,string> */
    private function describeNamedOptionValues(
        OptionDefinition $option,
        OptionParamsDefinition $value,
        string $info,
        string $description
    ): Generator {
        $info .= $option->getValueSeparator();
        $params = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($value->getParams() as $name => $default) {
            if ($default !== null) {
                $params[] = '[' . $name . '=' . json_encode($default) . ']';
            } else {
                $params[] = '[' . $name . ']';
            }
        }

        yield $info . implode(' ', $params) => $description;
    }
}
