<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Downloader;

use Phpcq\GnuPG\Exception\DownloadFailureException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class HttpClientDownloader implements FileDownloaderInterface
{
    /** @var ClientInterface */
    private $client;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory)
    {
        $this->client         = $client;
        $this->requestFactory = $requestFactory;
    }

    public function downloadFile(string $url) : string
    {
        try {
            $response = $this->client->sendRequest($this->requestFactory->createRequest('GET', $url));
        } catch (ClientExceptionInterface $exception) {
            throw new DownloadFailureException(
                sprintf('Downloading file from "%s" failed', $url),
                $exception->getCode(),
                $exception
            );
        }

        return $response->getBody()->getContents();
    }
}
