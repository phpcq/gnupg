<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Wrapper;

use Gnupg;
use Phpcq\GnuPG\GnuPGInterface;

/**
 * @psalm-type TGnupgImportResult = array{imported: int}
 * @psalm-import-type TKeyInfo from \Phpcq\GnuPG\GnuPGInterface
 *
 * @psalm-import-type TVerifyResult from \Phpcq\GnuPG\GnuPGInterface
 */
final class GnuPGExtensionWrapper implements GnuPGInterface
{
    /**
     * @var Gnupg
     */
    private $inner;

    /**
     * GnuPGDecorator constructor.
     */
    public function __construct(Gnupg $inner)
    {
        $this->inner = $inner;
    }

    /**
     * @inheritDoc
     * @psalm-return TGnupgImportResult
     */
    public function import(string $key): array
    {
        /** @psalm-var TGnupgImportResult|false $result */
        $result = $this->inner->import($key);
        if ($result === false) {
            return ['imported' => 0];
        }

        return $result;
    }

    /** @inheritDoc */
    public function keyinfo(string $search): array
    {
        /** @psalm-var TKeyInfo */
        $keyinfo = $this->inner->keyinfo($search);

        return $keyinfo;
    }

    /** @inheritDoc */
    public function verify(string $message, ?string $signature = null)
    {
        /** @psalm-var TVerifyResult $result */
        $result = $this->inner->verify($message, $signature);

        return $result;
    }
}
