<?php

declare(strict_types=1);

namespace Phpcq\Command;

use Phpcq\GnuPG\Signature\AlwaysStrategy;
use Phpcq\GnuPG\Signature\TrustedKeysStrategy;
use Phpcq\GnuPG\Signature\TrustKeyStrategyInterface;
use Phpcq\Signature\InteractiveQuestionKeyTrustStrategy;

trait UntrustedKeyStrategyTrait
{
    protected function getUntrustedKeyStrategy(): TrustKeyStrategyInterface
    {
        if ($this->input->getOption('trust-keys')) {
            return AlwaysStrategy::TRUST();
        }

        return new InteractiveQuestionKeyTrustStrategy(
            new TrustedKeysStrategy($this->config['trusted-keys']),
            $this->input,
            $this->output,
            $this->getHelper('question')
        );
    }
}
