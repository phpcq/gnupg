<?php

declare(strict_types=1);

namespace spec\Phpcq\GnuPG\Signature;

use Phpcq\GnuPG\Signature\TrustedKeysStrategy;
use Phpcq\GnuPG\Signature\TrustKeyStrategyInterface;
use PhpSpec\ObjectBehavior;

final class TrustedKeysStrategySpec extends ObjectBehavior
{
    public function let() : void
    {
        $this->beConstructedWith(['foo', 'bar']);
    }

    public function it_is_initializable() : void
    {
        $this->shouldHaveType(TrustedKeysStrategy::class);
    }

    public function it_is_a_trust_key_strategy() : void
    {
        $this->shouldImplement(TrustKeyStrategyInterface::class);
    }

    public function it_trust_defined_keys() : void
    {
        $this->isTrusted('foo')->shouldReturn(true);
        $this->isTrusted('bar')->shouldReturn(true);
    }

    public function it_rejects_undefined_keys() : void
    {
        $this->isTrusted('baz')->shouldReturn(false);
    }
}
