<?php

declare(strict_types=1);

namespace Phpcq\Test\Runner\Repository;

use Phpcq\Exception\PluginVersionNotFoundException;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\Runner\Repository\RepositoryHasPluginVersionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Repository\RepositoryHasPluginVersionTrait
 */
class RepositoryHasPluginVersionTraitTest extends TestCase
{
    public function testCallsGetToolAndReturnsTrueOnMatch(): void
    {
        $trait = $this->getMockBuilder(RepositoryHasPluginVersionTrait::class)->getMockForTrait();
        $trait
            ->method('getPluginVersion')
            ->withConsecutive(['superplugin', '1.0.0.0'], ['superplugin', '1.0.0.1'])
            ->willReturnOnConsecutiveCalls(
                $this->createMock(PluginVersionInterface::class),
                $this->throwException(new PluginVersionNotFoundException('superplugin', '1.0.0.1'))
            );

        /** @var RepositoryHasPluginVersionTrait $trait */
        $this->assertTrue($trait->hasPluginVersion('superplugin', '1.0.0.0'));
        $this->assertFalse($trait->hasPluginVersion('superplugin', '1.0.0.1'));
    }
}
