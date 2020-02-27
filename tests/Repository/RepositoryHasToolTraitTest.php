<?php

declare(strict_types=1);

namespace Phpcq\Test\Repository;

use Phpcq\Exception\ToolNotFoundException;
use Phpcq\Repository\RepositoryHasToolTrait;
use Phpcq\Repository\ToolInformationInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\Repository\RepositoryHasToolTrait
 */
class RepositoryHasToolTraitTest extends TestCase
{
    public function testCallsGetToolAndReturnsTrueOnMatch(): void
    {
        $trait = $this->getMockBuilder(RepositoryHasToolTrait::class)->getMockForTrait();
        $trait
            ->method('getTool')
            ->withConsecutive(['supertool', '1.0.0.0'], ['supertool', '1.0.0.1'])
            ->willReturnOnConsecutiveCalls(
                $this->createMock(ToolInformationInterface::class),
                $this->throwException(new ToolNotFoundException('supertool', '1.0.0.1'))
            );

        /** @var RepositoryHasToolTrait $trait */
        $this->assertTrue($trait->hasTool('supertool', '1.0.0.0'));
        $this->assertFalse($trait->hasTool('supertool', '1.0.0.1'));
    }
}
