<?php

declare(strict_types=1);

namespace Phpcq\Runner\Test\Signature;

use Phpcq\GnuPG\Signature\TrustKeyStrategyInterface;
use Phpcq\Runner\Signature\InteractiveQuestionKeyTrustStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(InteractiveQuestionKeyTrustStrategy::class)]
final class InteractiveQuestionKeyTrustStrategyTest extends TestCase
{
    public static function trueProvider(): iterable
    {
        yield 'Known fingerprint' => [
            'fingerprint' => '0123456789ABCDEF',
            'strategyProvider' => function (TestCase $test): TrustKeyStrategyInterface {
                $strategy = $test->getMockBuilder(TrustKeyStrategyInterface::class)->getMock();
                $strategy
                    ->expects($test->once())
                    ->method('isTrusted')
                    ->with('0123456789ABCDEF')
                    ->willReturn(true);

                return $strategy;
            }
        ];

        yield 'Known long fingerprint' => [
            'fingerprint' => '00000000000000000123456789ABCDEF',
            'strategyProvider' => function (TestCase $test): TrustKeyStrategyInterface {
                $strategy = $test->getMockBuilder(TrustKeyStrategyInterface::class)->getMock();
                $strategy
                    ->expects($test->once())
                    ->method('isTrusted')
                    ->with('00000000000000000123456789ABCDEF')
                    ->willReturn(true);

                return $strategy;
            }
        ];

        yield 'Known long fingerprint is retried as short' => [
            'fingerprint' => '00000000000000000123456789ABCDEF',
            'strategyProvider' => function (TestCase $test): TrustKeyStrategyInterface {
                $strategy = $test->getMockBuilder(TrustKeyStrategyInterface::class)->getMock();
                $strategy
                    ->expects($test->exactly(2))
                    ->method('isTrusted')
                    ->willReturnCallback(static function (string $fingerprint) {
                        static $invocation = 0;
                        switch ($invocation++) {
                            case 0:
                                self::assertSame('00000000000000000123456789ABCDEF', $fingerprint);
                                return false;
                            case 1:
                                self::assertSame('0123456789ABCDEF', $fingerprint);
                                return true;
                            default:
                        }
                        self::fail('Unexpected invocation');
                    });

                return $strategy;
            }
        ];
    }

    #[DataProvider('trueProvider')]
    /** @param callable(TestCase): TrustKeyStrategyInterface $strategyProvider */
    public function testIsTrustedReturnsTrue(
        string $fingerprint,
        callable $strategyProvider,
    ): void {
        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();
        $helper = new QuestionHelper();

        $interactiveStrategy = new InteractiveQuestionKeyTrustStrategy(
            $strategyProvider($this),
            $input,
            $output,
            $helper
        );

        self::assertTrue($interactiveStrategy->isTrusted($fingerprint));
    }
}
