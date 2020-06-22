<?php

declare(strict_types=1);

namespace Phpcq\Config\Builder;

use Phpcq\PluginApi\Version10\Exception\InvalidConfigurationException;
use function sprintf;

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
        if (null === $options) {
            if (!$this->required) {
                return;
            }

            throw new InvalidConfigurationException(sprintf('Configuration key "%s" has to be set', $this->name));
        }

        if ($this->bypassValueValidation) {
            foreach ($this->validators as $validator) {
                $validator($options);
            }
            return;
        }

        parent::validateValue($options);
    }
}
