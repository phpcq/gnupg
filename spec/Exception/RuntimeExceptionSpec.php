<?php

declare(strict_types=1);

namespace spec\Phpcq\GnuPG\Exception;

use Phpcq\GnuPG\Exception\RuntimeException;
use PhpSpec\ObjectBehavior;
use Phpcq\GnuPG\Exception\Exception;

final class RuntimeExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable() : void
    {
        $this->shouldHaveType(RuntimeException::class);
    }

    public function it_is_a_phpcq_gnupg_exception() : void
    {
        $this->shouldHaveType(Exception::class);
    }

    public function it_constructs_with_parameters(Exception $exception) : void
    {
        $this->beConstructedWith('Message', 1, $exception->getWrappedObject());

        $this->getMessage()->shouldReturn('Message');
        $this->getCode()->shouldReturn(1);
        $this->getPrevious()->shouldReturn($exception);
    }
}
