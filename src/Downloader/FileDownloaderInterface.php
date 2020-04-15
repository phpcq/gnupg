<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Downloader;

use Phpcq\GnuPG\Exception\DownloadFailureException;

interface FileDownloaderInterface
{
    /**
     * Downloads a file and returns it content.
     *
     * @throws DownloadFailureException When a downloadKey error occurs.
     */
    public function downloadFile(string $url) : string;
}
