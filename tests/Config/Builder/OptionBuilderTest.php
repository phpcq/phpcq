<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Builder;

use Phpcq\Config\Builder\OptionBuilder;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Config\Builder\OptionBuilder */
final class OptionBuilderTest extends TestCase
{
    use OptionBuilderTestTrait;

    protected function createInstance(array $validators = []): OptionBuilder
    {
        return new OptionBuilder('Option', 'Example option', $validators);
    }
}
