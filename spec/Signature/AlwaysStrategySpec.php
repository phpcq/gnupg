<?php

declare(strict_types=1);

namespace spec\Phpcq\GnuPG\Signature;

use Phpcq\GnuPG\Signature\TrustKeyStrategyInterface;
use PhpSpec\ObjectBehavior;
use Phpcq\GnuPG\Signature\AlwaysStrategy;

final class AlwaysStrategySpec extends ObjectBehavior
{
    public function let() : void
    {
        $this->beConstructedThrough('TRUST');
    }

    public function it_is_initializable() : void
    {
        $this->shouldHaveType(AlwaysStrategy::class);
    }

    public function it_is_a_trust_key_strategy() : void
    {
        $this->shouldImplement(TrustKeyStrategyInterface::class);
    }

    public function it_always_trust_keys_constructed_throug_accept(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->isTrusted('foo' . $i)->shouldReturn(true);
        }
    }

    public function it_always_reject_keys_constructed_throught_reject(): void
    {
        $this->beConstructedThrough('REJECT');

        for ($i = 0; $i < 100; $i++) {
            $this->isTrusted('foo' . $i)->shouldReturn(false);
        }
    }
}
