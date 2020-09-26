<?php

declare(strict_types=1);

namespace spec\Phpcq\GnuPG\Signature;

use Phpcq\GnuPG\Downloader\FileDownloaderInterface;
use Phpcq\GnuPG\Downloader\KeyDownloader;
use Phpcq\GnuPG\GnuPGInterface;
use Phpcq\GnuPG\Signature\TrustKeyStrategyInterface;
use PhpSpec\ObjectBehavior;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use Prophecy\Argument;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
final class SignatureVerifierSpec extends ObjectBehavior
{
    public function let(
        GnuPGInterface $gnupg,
        FileDownloaderInterface $fileDownloader,
        TrustKeyStrategyInterface $strategy
    ): void {
        $fileDownloader->downloadFile(Argument::type('string'))->willReturn('ABCD');

        $this->beConstructedWith($gnupg, new KeyDownloader($fileDownloader->getWrappedObject()), $strategy);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(SignatureVerifier::class);
    }

    public function it_rejects_unknown_key_by_default(
        GnuPGInterface $gnupg,
        FileDownloaderInterface $fileDownloader
    ): void {
        $this->beConstructedWith($gnupg, new KeyDownloader($fileDownloader->getWrappedObject()));

        $gnupg->verify('foo', 'foobar')
            ->shouldBeCalled()
            ->willReturn([['fingerprint' => 'ABCD', 'summary' => 128]]);

        $this->verify('foo', 'foobar')->isUntrustedKey()->shouldReturn(true);
    }

    public function it_imports_trusted_keys_and_verifies_again(
        GnuPGInterface $gnupg,
        FileDownloaderInterface $fileDownloader,
        TrustKeyStrategyInterface $strategy
    ) {
        $strategy->isTrusted('ABCD')
            ->shouldBeCalled()
            ->willReturn(true);

        $gnupg->verify('foo', 'foobar')
            ->shouldBeCalledTimes(2)
            ->willReturn([['fingerprint' => 'ABCD', 'summary' => 128]]);

        $fileDownloader->downloadFile(Argument::type('string'))->shouldBeCalled();
        $gnupg->import(Argument::type('string'))
            ->willReturn(['imported' => 1, 'fingerprint' => 'ABCD']);

        $this->verify('foo', 'foobar');
    }

    public function it_does_not_import_untrusted_key(
        GnuPGInterface $gnupg,
        FileDownloaderInterface $fileDownloader,
        TrustKeyStrategyInterface $strategy
    ) {
        $strategy->isTrusted('ABCD')
            ->shouldBeCalled()
            ->willReturn(false);

        $gnupg->verify('foo', 'foobar')
            ->shouldBeCalled()
            ->willReturn([['fingerprint' => 'ABCD', 'summary' => 128]]);

        $fileDownloader->downloadFile(Argument::type('string'))->shouldNotBeCalled();
        $gnupg->import(Argument::type('string'))
            ->shouldNotBeCalled();

        $this->verify('foo', 'foobar');
    }

    public function it_returns_unknown_error_if_verify_has_an_error(GnuPGInterface $gnupg): void
    {
        $gnupg->verify('foo', 'foobar')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->verify('foo', 'foobar')->isUnknownError()->shouldReturn(true);
    }
}
