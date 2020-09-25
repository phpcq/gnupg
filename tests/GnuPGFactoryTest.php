<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Test;

use Phpcq\GnuPG\Exception\RuntimeException;
use Phpcq\GnuPG\GnuPGFactory;
use Phpcq\GnuPG\Wrapper\GnuPGBinaryWrapper;
use Phpcq\GnuPG\Wrapper\GnuPGExtensionWrapper;
use PHPUnit\Framework\TestCase;

use function exec;
use function explode;
use function extension_loaded;
use function getcwd;
use function sprintf;
use function stripos;
use function sys_get_temp_dir;

use const PHP_OS;

/** @covers \Phpcq\GnuPG\GnuPGFactory */
final class GnuPGFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new GnuPGFactory(sys_get_temp_dir());

        if (extension_loaded('gnupg')) {
            $this->assertInstanceOf(GnuPGExtensionWrapper::class, $factory->create(getcwd()));

            return;
        }

        $binary = $this->findBinary();
        if ($binary !== null) {
            $this->assertInstanceOf(GnuPGBinaryWrapper::class, $factory->create(getcwd()));
            return;
        }

        $this->expectException(RuntimeException::class);

        $factory->create(getcwd());
    }

    public function testCreateBinaryWrapper(): void
    {
        $binary = $this->findBinary();
        if ($binary === null) {
            $this->markTestSkipped('Gnupg binary not found.');

            return;
        }

        $factory = new GnuPGFactory(sys_get_temp_dir());
        $instance = $factory->createBinaryWrapper(getcwd());

        $this->assertInstanceOf(GnuPGBinaryWrapper::class, $instance);
    }

    public function testCreateExtensionWrapper(): void
    {
        if (!extension_loaded('gnupg')) {
            $this->markTestSkipped('Gnupg extension not loaded.');

            return;
        }

        $factory = new GnuPGFactory(sys_get_temp_dir());
        $instance = $factory->createExtensionWrapper(getcwd());

        $this->assertInstanceOf(GnuPGExtensionWrapper::class, $instance);
    }

    /** @SuppressWarnings(PHPMD.UnusedLocalVariable) */
    private function findBinary(): ?string
    {
        $which  = (stripos(PHP_OS, 'WIN') === 0) ? 'where.exe' : 'which';
        $result = exec(sprintf('%s %s', $which, 'gpg'), $output, $exitCode);

        if ($exitCode !== 0) {
            return null;
        }

        return explode("\n", $result)[0];
    }
}
