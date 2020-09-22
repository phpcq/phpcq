<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\Exception\ToolVersionNotFoundException;
use Phpcq\Runner\Repository\RepositoryHasToolVersionTrait;
use Phpcq\Repository\ToolInformationInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Repository\RepositoryHasToolVersionTrait
 */
class RepositoryHasToolTraitTest extends TestCase
{
    public function testCallsGetToolAndReturnsTrueOnMatch(): void
    {
        $trait = $this->getMockBuilder(RepositoryHasToolVersionTrait::class)->getMockForTrait();
        $trait
            ->method('getPluginVersion')
            ->withConsecutive(['supertool', '1.0.0.0'], ['supertool', '1.0.0.1'])
            ->willReturnOnConsecutiveCalls(
                $this->createMock(ToolInformationInterface::class),
                $this->throwException(new ToolVersionNotFoundException('supertool', '1.0.0.1'))
            );

        /** @var RepositoryHasToolVersionTrait $trait */
        $this->assertTrue($trait->hasToolVersion('supertool', '1.0.0.0'));
        $this->assertFalse($trait->hasToolVersion('supertool', '1.0.0.1'));
    }
}
