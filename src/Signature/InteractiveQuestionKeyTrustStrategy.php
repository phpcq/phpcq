<?php

declare(strict_types=1);

namespace Phpcq\Runner\Signature;

use Override;
use Phpcq\GnuPG\Signature\TrustedKeysStrategy;
use Phpcq\GnuPG\Signature\TrustKeyStrategyInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function sprintf;

final readonly class InteractiveQuestionKeyTrustStrategy implements TrustKeyStrategyInterface
{
    public function __construct(
        private TrustedKeysStrategy $trustedKeys,
        private InputInterface $input,
        private OutputInterface $output,
        private QuestionHelper $questionHelper
    ) {
    }

    #[Override]
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
