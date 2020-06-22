<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

final class OptionsBuilder extends AbstractOptionsBuilder
{
    /** @var bool */
    private $bypassValueValidation = false;

    /**
     * // FIXME: Remove as soon we can validate referenced configurations
     *
     * @internal
     * @deprecated
     */
    public function bypassValueValidation(): void
    {
        $this->bypassValueValidation = true;
    }

    public function validateValue($options) : void
    {
        if ($this->bypassValueValidation) {
            foreach ($this->validators as $validator) {
                $validator($options);
            }
            return;
        }

        parent::validateValue($options);
    }
}
