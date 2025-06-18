<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\OptionValue;

final class OptionParamsDefinition extends OptionValueDefinition
{
    /** @param array<string,mixed> $params */
    public function __construct(bool $required, private readonly array $params)
    {
        parent::__construct($required);
    }

    /** @return array<string,mixed> */
    public function getParams(): array
    {
        return $this->params;
    }
}
