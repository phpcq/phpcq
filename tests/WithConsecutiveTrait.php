<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test;

trait WithConsecutiveTrait
{
    protected function consecutiveCalls(mixed ...$args): callable
    {
        $count = 0;
        return function ($arg) use (&$count, $args) {
            return $arg === $args[$count++];
        };
    }
}
