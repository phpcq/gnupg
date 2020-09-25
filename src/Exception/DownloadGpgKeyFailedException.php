<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Exception;

use Throwable;

use function implode;

final class DownloadGpgKeyFailedException extends RuntimeException
{
    /** @var string */
    private $fingerprint;

    /**
     * @psalm-var list<string>
     * @var string[]
     */
    private $keyServers;

    /** @psalm-param list<string> $keyServers */
    public function __construct(
        string $fingerprint,
        array $keyServers,
        ?string $message = null,
        int $code = 0,
        Throwable $previous = null
    ) {
        if (null === $message) {
            $message = sprintf(
                'Downloading GPG key with fingerprint "%s" from servers "%s" failed',
                $fingerprint,
                implode(', ', $keyServers)
            );
        }

        parent::__construct($message, $code, $previous);

        $this->fingerprint = $fingerprint;
        $this->keyServers  = $keyServers;
    }

    /**
     * @psalm-return list<string>
     * @return string[]
     */
    public function getKeyServers(): array
    {
        return $this->keyServers;
    }

    /**
     * Get the request gpg key fingerprint.
     *
     * @return string|null
     */
    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }
}
