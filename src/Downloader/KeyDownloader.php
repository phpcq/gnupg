<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Downloader;

use Phpcq\GnuPG\Exception\DownloadFailureException;
use Phpcq\GnuPG\Exception\DownloadGpgKeyFailedException;

final class KeyDownloader
{
    private const DEFAULT_KEYSERVERS = [
        // GnuPG provides no user ID for some keys which leads to errors with current versions of GnuPG
        // See https://keys.openpgp.org/about/faq#older-gnupg
        // Prefer hkps.pool.sks-keyservers.net instead
        // hkps.pool.sks-keyservers.net uses a self-signed cert so you might add the cert file to your file downloader
        // implementation
        'hkps.pool.sks-keyservers.net',
        'keys.openpgp.org',
        'keyserver.ubuntu.com'
    ];

    /** @var string[] */
    private $keyServers;

    /**
     * @var FileDownloaderInterface
     */
    private $fileDownloader;

    public function __construct(
        FileDownloaderInterface $fileDownloader,
        ?array $keyServers = null
    ) {
        $this->fileDownloader = $fileDownloader;
        $this->keyServers     = $keyServers ?: self::DEFAULT_KEYSERVERS;
    }

    public function downloadKey(string $keyId) : string
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

    private function createUri(string $keyId, string $keyServer) : string
    {
        return sprintf('https://%s/pks/lookup?op=get&options=mr&search=0x%s', $keyServer, $keyId);
    }
}
