<?php

declare(strict_types=1);

namespace Phpcq\Runner\Signature;

use Phpcq\GnuPG\Signature\TrustedKeysStrategy;
use Phpcq\GnuPG\Signature\TrustKeyStrategyInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function sprintf;

final class InteractiveQuestionKeyTrustStrategy implements TrustKeyStrategyInterface
{
    /** @var TrustedKeysStrategy */
    private $trustedKeys;

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /** @var QuestionHelper */
    private $questionHelper;

    public function __construct(
        TrustedKeysStrategy $trustedKeysStrategy,
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $questionHelper
    ) {
        $this->trustedKeys    = $trustedKeysStrategy;
        $this->input          = $input;
        $this->output         = $output;
        $this->questionHelper = $questionHelper;
    }

    #[\Override]
    public function isTrusted(string $fingerprint): bool
    {
        if ($this->trustedKeys->isTrusted($fingerprint)) {
            return true;
        }

        $question = new ConfirmationQuestion(
            sprintf('Temporary trust key "%s"? (y/n) ', $fingerprint),
            false
        );

        if (!$this->questionHelper->ask($this->input, $this->output, $question)) {
            return false;
        }

        $this->output->writeln(
            sprintf(
                'Temporary accepted key "%s". For permanent acceptance add it to the trusted-keys section of your '
                . 'configuration.',
                $fingerprint
            )
        );

        return true;
    }
}
