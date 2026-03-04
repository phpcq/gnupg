<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Test\Signature;

use Phpcq\GnuPG\Downloader\FileDownloaderInterface;
use Phpcq\GnuPG\Downloader\KeyDownloader;
use Phpcq\GnuPG\Exception\RuntimeException;
use Phpcq\GnuPG\GnuPGFactory;
use Phpcq\GnuPG\Signature\SignatureVerifier;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\GnuPG\Signature\SignatureVerifier */
final class SignatureVerifierTest extends TestCase
{
    public function testExtractingInformationWithBinaryWrapper(): void
    {
        $factory = new GnuPGFactory(sys_get_temp_dir());
        try {
            $gpg = $factory->createBinaryWrapper(sys_get_temp_dir());
        } catch (RuntimeException $exception) {
            self::markTestSkipped('gnupg extension not loaded');
        }

        $fileDownloader = $this->createMock(FileDownloaderInterface::class);
        $fileDownloader->expects($this->never())->method('downloadFile');
        $keyDownloader = new KeyDownloader($fileDownloader);

        $verifier = new SignatureVerifier($gpg, $keyDownloader);

        $baseDir = dirname(__DIR__);

        $result = $verifier->verify(
            file_get_contents($baseDir . '/fixtures/test.txt'),
            file_get_contents($baseDir . '/fixtures/test.txt.asc')
        );

        self::assertTrue($result->isUntrustedKey());
        self::assertSame('11BD3E86C5497BA1EB3B58B96DA9559564C3328F', $result->getFingerprint());
    }

    public function testExtractingInformationWithExtension(): void
    {
        $factory = new GnuPGFactory(sys_get_temp_dir());
        try {
            $gpg = $factory->createExtensionWrapper(sys_get_temp_dir());
        } catch (RuntimeException $exception) {
            self::markTestSkipped('gnupg extension not loaded');
        }

        $fileDownloader = $this->createMock(FileDownloaderInterface::class);
        $fileDownloader->expects($this->never())->method('downloadFile');
        $keyDownloader = new KeyDownloader($fileDownloader);

        $verifier = new SignatureVerifier($gpg, $keyDownloader);

        $baseDir = dirname(__DIR__);

        $result = $verifier->verify(
            file_get_contents($baseDir . '/fixtures/test.txt'),
            file_get_contents($baseDir . '/fixtures/test.txt.asc')
        );

        self::assertTrue($result->isUntrustedKey());
        self::assertSame('11BD3E86C5497BA1EB3B58B96DA9559564C3328F', $result->getFingerprint());
    }
}
