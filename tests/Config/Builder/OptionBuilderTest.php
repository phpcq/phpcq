<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Builder;

use Phpcq\Config\Builder\OptionBuilder;
use Phpcq\PluginApi\Version10\Configuration\Builder\NodeBuilderInterface;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\Config\Builder\OptionBuilder */
final class OptionBuilderTest extends TestCase
{
    use OptionBuilderTestTrait;

    protected function createInstance(?NodeBuilderInterface $parent = null, array $validators = []): OptionBuilder
    {
        $parent = $parent ?: $this->getMockForAbstractClass(NodeBuilderInterface::class);

        return new OptionBuilder($parent, 'Option', 'Example option', $validators);
    }
}