<?php

declare(strict_types=1);

namespace spec\Phpcq\GnuPG\Exception;

use Phpcq\GnuPG\Exception\DownloadFailureException;
use Phpcq\GnuPG\Exception\DownloadGpgKeyFailedException;
use Phpcq\GnuPG\Exception\RuntimeException;
use PhpSpec\ObjectBehavior;
use Phpcq\GnuPG\Exception\Exception;

final class DownloadGpgKeyFailedExceptionSpec extends ObjectBehavior
{
    public function let() : void
    {
        $this->beConstructedWith('ABCDEF', ['example.org']);
    }

    public function it_is_initializable() : void
    {
        $this->shouldHaveType(DownloadGpgKeyFailedException::class);
    }

    public function it_is_a_runtime_exception() : void
    {
        $this->shouldHaveType(RuntimeException::class);
    }

    public function it_constructs_with_parameters(Exception $exception) : void
    {
        $this->beConstructedWith('ABCD', ['example.org'], 'Message', 1, $exception->getWrappedObject());

        $this->getFingerprint()->shouldReturn('ABCD');
        $this->getKeyServers()->shouldReturn(['example.org']);
        $this->getMessage()->shouldReturn('Message');
        $this->getCode()->shouldReturn(1);
        $this->getPrevious()->shouldReturn($exception);
    }

    public function it_creates_default_message(Exception $exception) : void
    {
        $this->beConstructedWith('ABCD', ['example.org']);

        $this->getFingerprint()->shouldReturn('ABCD');
        $this->getKeyServers()->shouldReturn(['example.org']);
    }
}
