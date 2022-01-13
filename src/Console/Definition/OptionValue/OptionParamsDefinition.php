<?php

declare(strict_types=1);

namespace Phpcq\Runner\Console\Definition\OptionValue;

final class OptionParamsDefinition extends OptionValueDefinition
{
    /**
     * @var array<string,mixed>
     */
    private $params;

    /** @param array<string,mixed> $params */
    public function __construct(bool $required, array $params)
    {
        parent::__construct($required);

        $this->params = $params;
    }

    /** @return array<string,mixed> */
    public function getParams(): array
    {
        return $this->params;
    }
}
