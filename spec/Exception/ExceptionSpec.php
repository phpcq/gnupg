<?php

declare(strict_types=1);

namespace spec\Phpcq\GnuPG\Exception;

use Exception as BaseException;
use PhpSpec\ObjectBehavior;
use Phpcq\GnuPG\Exception\Exception;

final class ExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable() : void
    {
        $this->shouldHaveType(Exception::class);
    }

    public function it_is_a_php_exception() : void
    {
        $this->shouldHaveType(BaseException::class);
    }

    public function it_constructs_with_parameters(BaseException $exception) : void
    {
        $this->beConstructedWith('Message', 1, $exception->getWrappedObject());

        $this->getMessage()->shouldReturn('Message');
        $this->getCode()->shouldReturn(1);
        $this->getPrevious()->shouldReturn($exception);
    }
}
