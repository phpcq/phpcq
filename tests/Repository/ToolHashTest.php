<?php

declare(strict_types=1);

namespace Repository;

use JsonSerializable;
use Phpcq\Repository\ToolHash;
use PHPUnit\Framework\TestCase;

use function sha1;

/**
 * @covers \Phpcq\Repository\ToolHash
 */
final class ToolHashTest extends TestCase
{
    public function testInstantiation(): void
    {
        $hash = new ToolHash(ToolHash::SHA_1, sha1('content'));
        $this->assertInstanceOf(ToolHash::class, $hash);
        $this->assertEquals(ToolHash::SHA_1, $hash->getType());
        $this->assertEquals(sha1('content'), $hash->getValue());
    }

    public function equalsProvider(): array
    {
        return [
            'equals with identical type and value' => [
                'expected'  => true,
                'left_type' => ToolHash::SHA_1,
                'left_value' => 'content',
                'right_type' => ToolHash::SHA_1,
                'right_value' => 'content'
            ],
            'does not equal with identical type but different value' => [
                'expected'  => false,
                'left_type' => ToolHash::SHA_1,
                'left_value' => 'content',
                'right_type' => ToolHash::SHA_1,
                'right_value' => 'bar'
            ],
            'does not equal with different type but identical value' => [
                'expected'  => false,
                'left_type' => ToolHash::SHA_1,
                'left_value' => 'content',
                'right_type' => ToolHash::SHA_256,
                'right_value' => 'bar'
            ],
        ];
    }
}
