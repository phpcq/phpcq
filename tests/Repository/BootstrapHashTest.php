<?php

declare(strict_types=1);

namespace Repository;

use JsonSerializable;
use Phpcq\Repository\BootstrapHash;
use PHPUnit\Framework\TestCase;

use function sha1;

/**
 * @covers \Phpcq\Repository\BootstrapHash
 */
final class BootstrapHashTest extends TestCase
{
    public function testInstantiation(): void
    {
        $hash = new BootstrapHash(BootstrapHash::SHA_1, sha1('content'));
        $this->assertInstanceOf(BootstrapHash::class, $hash);
        $this->assertEquals(BootstrapHash::SHA_1, $hash->getType());
        $this->assertEquals(sha1('content'), $hash->getValue());
    }

    public function equalsProvider(): array
    {
        return [
            'equals with identical type and value' => [
                'expected'  => true,
                'left_type' => BootstrapHash::SHA_1,
                'left_value' => 'content',
                'right_type' => BootstrapHash::SHA_1,
                'right_value' => 'content'
            ],
            'does not equal with identical type but different value' => [
                'expected'  => false,
                'left_type' => BootstrapHash::SHA_1,
                'left_value' => 'content',
                'right_type' => BootstrapHash::SHA_1,
                'right_value' => 'bar'
            ],
            'does not equal with different type but identical value' => [
                'expected'  => false,
                'left_type' => BootstrapHash::SHA_1,
                'left_value' => 'content',
                'right_type' => BootstrapHash::SHA_256,
                'right_value' => 'bar'
            ],
        ];
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(
        bool $expected,
        string $leftType,
        string $leftValue,
        string $rightType,
        string $rightValue
    ): void {
        $left = new BootstrapHash($leftType, $leftValue);
        $right = new BootstrapHash($rightType, $rightValue);

        $this->assertEquals($expected, $left->equals($right));
        $this->assertEquals($expected, $right->equals($left));
    }
}
