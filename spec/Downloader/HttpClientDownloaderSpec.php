<?php

declare(strict_types=1);

namespace spec\Phpcq\GnuPG\Downloader;

use Phpcq\GnuPG\Downloader\FileDownloaderInterface;
use Phpcq\GnuPG\Exception\DownloadFailureException;
use PhpSpec\ObjectBehavior;
use Phpcq\GnuPG\Downloader\HttpClientDownloader;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
final class HttpClientDownloaderSpec extends ObjectBehavior
{
    public function let(ClientInterface $client, RequestFactoryInterface $requestFactory): void
    {
        $this->beConstructedWith($client, $requestFactory);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(HttpClientDownloader::class);
    }

    public function it_is_a_file_downloader(): void
    {
        $this->shouldImplement(FileDownloaderInterface::class);
    }

    public function it_downloads_file_from_client(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        RequestInterface $request,
        ResponseInterface $response,
        StreamInterface $body
    ): void {
        $requestFactory->createRequest('GET', 'https://example.org/foo/bar.xml')->willReturn($request);
        $response->getBody()->willReturn($body);
        $body->getContents()->willReturn('bar');

        $client->sendRequest($request)
            ->shouldBeCalledOnce()
            ->willReturn($response);

        $this->downloadFile('https://example.org/foo/bar.xml')
            ->shouldReturn('bar');
    }

    public function it_throws_download_failure(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        ClientExceptionInterface $exception,
        RequestInterface $request
    ): void {
        $requestFactory->createRequest('GET', 'https://example.org/foo/bar.xml')->willReturn($request);
        $client->sendRequest($request)->willThrow($exception->getWrappedObject());

        $this->shouldThrow(DownloadFailureException::class)
            ->during('downloadFile', ['https://example.org/foo/bar.xml']);
    }
}
