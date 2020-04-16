<?php

declare(strict_types=1);

namespace spec\Phpcq\GnuPG\Downloader;

use Phpcq\GnuPG\Downloader\FileDownloaderInterface;
use Phpcq\GnuPG\Exception\DownloadFailureException;
use Phpcq\GnuPG\Exception\DownloadGpgKeyFailedException;
use PhpSpec\ObjectBehavior;
use Phpcq\GnuPG\Downloader\KeyDownloader;
use Prophecy\Argument;

final class KeyDownloaderSpec extends ObjectBehavior
{
    public function let(FileDownloaderInterface $fileDownloader) : void
    {
        $this->beConstructedWith($fileDownloader);
    }

    public function it_is_initializable() : void
    {
        $this->shouldHaveType(KeyDownloader::class);
    }

    public function it_downloads_key_from_server(FileDownloaderInterface $fileDownloader) : void
    {
        $fileDownloader->downloadFile(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn('FOO');

        $this->downloadKey('ABCD')->shouldReturn('FOO');
    }

    public function it_tries_multiple_servers_until_success(FileDownloaderInterface $fileDownloader) : void
    {
        $servers = ['a.example.org', 'b.example.org', 'c.example.org'];
        $this->beConstructedWith($fileDownloader, $servers);

        $fileDownloader->downloadFile(Argument::containingString('a.example.org'))
            ->shouldBeCalledOnce()
            ->willThrow(new DownloadFailureException());

        $fileDownloader->downloadFile(Argument::containingString('b.example.org'))
            ->shouldBeCalledOnce()
            ->willReturn('FOO');

        $fileDownloader->downloadFile(Argument::containingString('c.example.org'))
            ->shouldNotBeCalled();

        $this->downloadKey('ABCD')->shouldReturn('FOO');
    }

    public function it_throws_excpetion_if_no_server_provides_key(FileDownloaderInterface $fileDownloader) : void
    {
        $fileDownloader->downloadFile(Argument::type('string'))
            ->shouldBeCalledTimes(3)
            ->willThrow(new DownloadFailureException());

        $this->shouldThrow(DownloadGpgKeyFailedException::class)
            ->during('downloadKey', ['ABCD']);
    }
}
