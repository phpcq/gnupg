<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Wrapper;

use Phpcq\GnuPG\Exception\Exception;
use Phpcq\GnuPG\Exception\RuntimeException;
use Phpcq\GnuPG\GnuPGInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use function array_merge;
use function explode;
use function file_put_contents;
use function uniqid;
use function unlink;

/**
 * Class wraps gnupg binary
 *
 * Implementation is heavily inspired by phar-io/gnupg and parsing result is copied from it.
 *
 * @see https://github.com/phar-io/gnupg/blob/master/src/GnuPG.php
 */
final class GnuPGBinaryWrapper implements GnuPGInterface
{
    /** @var string */
    private $binaryPath;

    /** @var string */
    private $tempDirectory;

    /** @var string */
    private $homeDirectory;

    /**
     * GnuGPBinaryVerifier constructor.
     */
    public function __construct(string $binaryPath, string $homeDirectory, string $tempDirectory)
    {
        $this->binaryPath    = $binaryPath;
        $this->tempDirectory = $tempDirectory;
        $this->homeDirectory = $homeDirectory;
    }

    public function import(string $key) : array
    {
        $tmpFile = $this->createTemporaryFile($key);

        $result = $this->execute(['--import', $tmpFile]);
        unlink($tmpFile);

        if (preg_match('=.*IMPORT_OK\s(\d+)\s(.*)=', $result, $matches)) {
            return [
                'imported'    => (int) $matches[1],
                'fingerprint' => $matches[2]
            ];
        }

        return ['imported' => 0];
    }

    public function keyinfo(string $search) : array
    {
        $command = [
            '--list-keys',
            '--with-fingerprint',
            '--with-fingerprint', // duplication intentional
            '--fixed-list-mode',
            $search
        ];

        $result = $this->execute($command);

        return $this->parseInfo($result);
    }

    public function verify(string $message, ?string $signature = null)
    {
        $messageFile   = $this->createTemporaryFile($message);
        $signatureFile = null;
        $command       = [
            '--verify'
        ];

        if (null !== $signature) {
            $signatureFile = $this->createTemporaryFile($signature);
            $command[]     = $signatureFile;
        }

        $command[] = $messageFile;

        $result = $this->execute($command);

        unlink($messageFile);
        unlink($signatureFile);

        return $this->parseVerifyOutput($result);
    }

    private function execute(array $arguments) : string
    {
        $command = array_merge($this->getDefaultCommand(), $arguments);
        $process = new Process($command);
        $process->run();

        return $process->getOutput();
    }

    private function getDefaultCommand() : array
    {
        return [
            $this->binaryPath,
            '--homedir',
            $this->homeDirectory,
            '--quiet',
            '--status-fd',
            '1',
            '--lock-multiple',
            '--no-permission-warning',
            '--no-greeting',
            '--exit-on-status-write-error',
            '--batch',
            '--no-tty',
            '--with-colons'
        ];
    }

    private function createTemporaryFile(string $content) : string
    {
        $tmpFile = $this->tempDirectory . '/' . uniqid('phpcq_gpg_', true);
        file_put_contents($tmpFile, $content);

        return $tmpFile;
    }

    private function parseInfo(string $result) : array
    {
        $key = [];
        $uids = [];
        $subkeys = [];

        foreach(explode("\n", $result) as $line) {
            $fragments = explode(':', $line);

            switch ($fragments[0]) {
                case 'sub':
                case 'pub':
                {
                    $subkeys[] = array_merge(
                        [
                            'keyid'     => $fragments[4],
                            'timestamp' => (int)$fragments[5],
                            'expires'   => (int)$fragments[6]
                        ],
                        $this->parseCapabilities($fragments[11]),
                        $this->parseValidity($fragments[1])
                    );

                    if (empty($key)) {
                        $key = array_merge(
                            $this->parseValidity($fragments[1]),
                            $this->parseCapabilities($fragments[11])
                        );
                    }
                    break;
                }

                case 'fpr':
                {
                    $subkeys[] = array_merge(
                        ['fingerprint' => $fragments[9]],
                        array_pop($subkeys)
                    );
                    break;
                }

                case 'uid':
                {
                    preg_match('/(.*)\s<(.*)>/', $fragments[9], $matches);

                    $uids[] = array_merge(
                        [
                            'name'    => $matches[1],
                            'comment' => '',
                            'email'   => $matches[2],
                            'uid'     => $fragments[9],
                        ],
                        $this->parseValidity($fragments[1])
                    );
                    break;
                }
            }
        }

        $key['uids'] = $uids;
        $key['subkeys'] = $subkeys;

        return [$key];
    }

    private function parseCapabilities(string $flags): array {
        /*
         * - e :: Encrypt
         * - s :: Sign
         * - c :: Certify
         * - a :: Authentication
         * - ? :: Unknown capability
         */

        $result = [
            'can_encrypt' => false,
            'can_sign'    => false
        ];

        static $map = [
            's' => 'can_sign',
            'e' => 'can_encrypt'
        ];

        foreach(\str_split(\strtolower($flags), 1) as $char) {
            if (isset($map[$char])) {
                $result[$map[$char]] = true;
            }
        }

        return $result;
    }

    private function parseValidity(string $flag): array {
        static $map = [
            'i' => 'invalid',
            'd' => 'disabled',
            'r' => 'revoked',
            'e' => 'expired',
            'n' => 'invalid'
        ];

        $parsed = [
            'disabled' => false,
            'expired'  => false,
            'revoked'  => false,
            'invalid'  => false
        ];

        if (isset($map[$flag])) {
            $parsed[$map[$flag]] = true;
        }

        return $parsed;
    }

    /** @return array|false */
    private function parseVerifyOutput(string $result)
    {
        $fingerprint = '';
        $timestamp = 0;
        $summary = false;
        $status = explode("\n", $result);

        foreach($status as $line) {
            $parts = explode(' ', $line);
            if (count($parts) < 3) {
                continue;
            }
            $fingerprint = $parts[2];

            if (strpos($line, 'VALIDSIG') !== false) {
                // [GNUPG:] VALIDSIG D8406D0D82947747{...}A394072C20A 2014-07-19 1405769272 0 4 0 1 10 00 D8{...}C20A
                /*
                VALIDSIG <args>
                The args are:
                - <fingerprint_in_hex>
                - <sig_creation_date>
                - <sig-timestamp>
                - <expire-timestamp>
                - <sig-version>
                - <reserved>
                - <pubkey-algo>
                - <hash-algo>
                - <sig-class>
                - [ <primary-key-fpr> ]
                */
                $timestamp = $parts[4];
                $summary = 0;
                break;
            }

            if (strpos($line, 'BADSIG') !== false) {
                // [GNUPG:] BADSIG 4AA394086372C20A Sebastian Bergmann <sb@sebastian-bergmann.de>
                $summary = 4;
                break;
            }

            if (strpos($line, 'ERRSIG') !== false) {
                // [GNUPG:] ERRSIG 4AA394086372C20A 1 10 00 1405769272 9
                // ERRSIG  <keyid>  <pkalgo> <hashalgo> <sig_class> <time> <rc>
                $timestamp = $parts[6];
                $summary = 128;
                break;
            }
        }

        if ($summary === false) {
            return false;
        }

        return [[
            'fingerprint' => $fingerprint,
            'validity'    => 0,
            'timestamp'   => $timestamp,
            'status'      => $status,
            'summary'     => $summary
        ]];
    }
}
