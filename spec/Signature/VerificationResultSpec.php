<?php

declare(strict_types=1);

namespace spec\Phpcq\GnuPG\Signature;

use Phpcq\GnuPG\Signature\VerificationResult;
use PhpSpec\ObjectBehavior;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
final class VerificationResultSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(VerificationResult::class);
    }

    public function it_describes_unknown_error(): void
    {
        $this->beConstructedThrough('UNKOWN_ERROR');

        $this->getFingerprint()->shouldBeNull();
        $this->isUnknownError()->shouldReturn(true);
        $this->isUntrustedKey()->shouldReturn(false);
        $this->isValid()->shouldReturn(false);
    }

    public function it_describes_untrusted_key(): void
    {
        $this->beConstructedThrough('UNTRUSTED_KEY', ['ABCD']);

        $this->getFingerprint()->shouldReturn('ABCD');
        $this->isUnknownError()->shouldReturn(false);
        $this->isUntrustedKey()->shouldReturn(true);
        $this->isValid()->shouldReturn(false);
    }

    public function it_describes_valid_key(): void
    {
        $this->beConstructedThrough('VALID', ['ABCD']);

        $this->getFingerprint()->shouldReturn('ABCD');
        $this->isUnknownError()->shouldReturn(false);
        $this->isUntrustedKey()->shouldReturn(false);
        $this->isValid()->shouldReturn(true);
    }
}
