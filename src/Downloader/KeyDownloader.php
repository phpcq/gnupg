<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Downloader;

use Phpcq\GnuPG\Exception\DownloadFailureException;
use Phpcq\GnuPG\Exception\DownloadGpgKeyFailedException;

final class KeyDownloader
{
    /**
     * @const string[]
     * @psalm-var list<string>
     */
    private const DEFAULT_KEYSERVERS = [
        'keyserver.ubuntu.com',
        'keys.openpgp.org',
    ];

    /**
     * @var string[]
     * @psalm-var list<string>
     */
    private $keyServers;

    /**
     * @var FileDownloaderInterface
     */
    private $fileDownloader;

    /** @psalm-param list<string> $keyServers */
    public function __construct(
        FileDownloaderInterface $fileDownloader,
        ?array $keyServers = null
    ) {
        $this->fileDownloader = $fileDownloader;
        $this->keyServers     = $keyServers ?: self::DEFAULT_KEYSERVERS;
    }

    public function downloadKey(string $keyId): string
    {
        foreach ($this->keyServers as $keyServer) {
            try {
                return $this->fileDownloader->downloadFile($this->createUri($keyId, $keyServer));
            } catch (DownloadFailureException $exception) {
                // Try next keyserver
            }
        }

        throw new DownloadGpgKeyFailedException($keyId, $this->keyServers);
    }

    private function createUri(string $keyId, string $keyServer): string
    {
        return sprintf('https://%s/pks/lookup?op=get&options=mr&search=0x%s', $keyServer, $keyId);
    }
}
