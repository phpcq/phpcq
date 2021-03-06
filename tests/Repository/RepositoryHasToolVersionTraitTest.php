<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Runner\Repository;

use Phpcq\Runner\Exception\ToolVersionNotFoundException;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\Runner\Repository\RepositoryHasToolVersionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Runner\Repository\RepositoryHasToolVersionTrait
 */
class RepositoryHasToolVersionTraitTest extends TestCase
{
    public function testCallsGetToolAndReturnsTrueOnMatch(): void
    {
        $trait = $this->getMockBuilder(RepositoryHasToolVersionTrait::class)->getMockForTrait();
        $trait
            ->method('getToolVersion')
            ->withConsecutive(['supertool', '1.0.0.0'], ['supertool', '1.0.0.1'])
            ->willReturnOnConsecutiveCalls(
                $this->createMock(ToolVersionInterface::class),
                $this->throwException(new ToolVersionNotFoundException('supertool', '1.0.0.1'))
            );

        /** @var RepositoryHasToolVersionTrait $trait */
        $this->assertTrue($trait->hasToolVersion('supertool', '1.0.0.0'));
        $this->assertFalse($trait->hasToolVersion('supertool', '1.0.0.1'));
    }
}
