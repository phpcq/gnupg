<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use Phpcq\GnuPG\Exception\Exception;

/**
 * Interface GnuPGInterface describes a subset of the supported features of the gnupg abstraction
 *
 * @psalm-type TKeyInfo = array{
 *  fingerprint?: string
 * }
 * @psalm-type TVerifyResultItem = array{
 *  fingerprint?: string,
 *  summary: int
 * }
 * @psalm-type TVerifyResult = false|list<TVerifyResultItem>
 */
interface GnuPGInterface
{
    /**
     * Imports a key and returns an array with information about the import process.
     *
     * @param string $key THe gpg key to import
     *
     * @return array
     *
     * @see https://www.php.net/manual/function.gnupg-import.php
     */
    public function import(string $key): array;

    /**
     * Returns an array with information about all keys that matches the given pattern.
     *
     * @param string $search The given search pattern.
     *
     * @return array
     *
     * @psalm-return TKeyInfo
     *
     * @see https://www.php.net/manual/en/function.gnupg-keyinfo.php
     */
    public function keyinfo(string $search): array;

    /**
     * Verifies the given signed_text and returns information about the signature.
     *
     * @param string      $message   The message to verify.
     * @param string|null $signature The signature. To verify a clearsigned text, set signature to null.
     *
     * @return array|false
     *
     * @psalm-return TVerifyResult
     *
     * @see https://www.php.net/manual/en/function.gnupg-verify.php
     */
    public function verify(string $message, ?string $signature = null);
}
