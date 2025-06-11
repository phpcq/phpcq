<?php

declare(strict_types=1);

namespace Phpcq\Runner\Config\Builder;

use Phpcq\Runner\Exception\ConfigurationValidationErrorException;
use Throwable;

use function sprintf;

/** @extends AbstractOptionsBuilder<array<string,mixed>> */
final class OptionsBuilder extends AbstractOptionsBuilder
{
    /** @var bool */
    private $bypassValidation = false;

    /**
     * FIXME: Remove as soon we can validate referenced configurations
     *
     * @internal
     * @deprecated
     */
    public function bypassValueValidation(): void
    {
        $this->bypassValidation = true;
    }

    #[\Override]
    public function validateValue($value): void
    {
        if (null === $value) {
            if (!$this->required) {
                return;
            }

            throw ConfigurationValidationErrorException::withCustomMessage(
                [$this->name],
                sprintf('Configuration key "%s" has to be set', $this->name)
            );
        }

        if ($this->bypassValidation) {
            try {
                foreach ($this->validators as $validator) {
                    $validator($value);
                }
            } catch (ConfigurationValidationErrorException $exception) {
                throw $exception->withOuterPath([$this->name]);
            } catch (Throwable $exception) {
                throw ConfigurationValidationErrorException::fromError([$this->name], $exception);
            }

            return;
        }

        parent::validateValue($value);
    }
}
