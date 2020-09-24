<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use Phpcq\Runner\Report\Buffer\FileRangeBuffer;

trait RenderRangeTrait
{
    private function renderRange(FileRangeBuffer $range): string
    {
        if (null === $value = $range->getStartLine()) {
            return '';
        }
        $result = '[' . (string) $value;
        if (null !== $value = $range->getStartColumn()) {
            $result .= ':' . (string) $value;
        }
        if (null !== $value = $range->getEndLine()) {
            $result .= ' - ' . (string) $value;
            if (null !== $value = $range->getEndColumn()) {
                $result .= ':' . (string) $value;
            }
        }

        return $result . ']';
    }
}
