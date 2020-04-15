<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Test\Wrapper;

use Phpcq\GnuPG\Exception\Exception;
use Phpcq\GnuPG\Test\stubs\Gnupg;
use Phpcq\GnuPG\Wrapper\GnuPGExtensionWrapper;
use PHPUnit\Framework\TestCase;
use function class_alias;
use function class_exists;
use function extension_loaded;

final class GnuPGExtensionWrapperTest extends TestCase
{
    public function setUp() : void
    {
        if (! extension_loaded('gnupg')) {
            if (!class_exists('\Gnupg')) {
                class_alias(Gnupg::class, '\Gnupg');
            }
        }
    }

    public function testImport() : void
    {
        $mock = $this->createMock(\Gnupg::class);

        $mock->expects($this->once())
            ->method('import')
            ->willReturn(['imported' => 1]);

        $instance = new GnuPGExtensionWrapper($mock);
        $instance->import('FOO');
    }

    public function testImportFailure() : void
    {
        $mock = $this->createMock(\Gnupg::class);

        $mock->expects($this->once())
            ->method('import')
            ->willReturn(['imported' => 0]);

        $this->expectException(Exception::class);

        $instance = new GnuPGExtensionWrapper($mock);
        $instance->import('FOO');
    }

    public function testKeyinfo(): void
    {
        $mock = $this->createMock(\Gnupg::class);

        $mock->expects($this->once())
            ->method('keyinfo')
            ->willReturn(['fingerprint' => 'ABCD']);

        $instance = new GnuPGExtensionWrapper($mock);
        $instance->keyinfo('FOO');
    }

    public function testVerify(): void
    {
        $mock = $this->createMock(\Gnupg::class);

        $mock->expects($this->once())
            ->method('verify')
            ->willReturn(['summary' => 0, 'fingerprint' => 'ABCD']);

        $instance = new GnuPGExtensionWrapper($mock);
        $instance->verify('foo', 'bar');
    }
}
