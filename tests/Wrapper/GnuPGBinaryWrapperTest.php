<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Test\Wrapper;

use Phpcq\GnuPG\Exception\Exception;
use Phpcq\GnuPG\Wrapper\GnuPGBinaryWrapper;
use PHPUnit\Framework\TestCase;

use function dirname;
use function exec;
use function explode;
use function file_get_contents;
use function mkdir;
use function sprintf;
use function stripos;
use function sys_get_temp_dir;

use const PHP_OS;

/** @covers \Phpcq\GnuPG\Wrapper\GnuPGBinaryWrapper */
final class GnuPGBinaryWrapperTest extends TestCase
{
    private $binary;

    private $homeDir;

    public function setUp(): void
    {
        $this->binary = $this->findBinary();

        if (null === $this->binary) {
            $this->markTestSkipped('Gnupg binary not found');
        }

        $this->homeDir = dirname(__DIR__) . '/home';
        mkdir($this->homeDir);
    }

    public function tearDown(): void
    {
        exec('rm -r -f ' . $this->homeDir);
    }

    public function testImport(): void
    {
        $instance = new GnuPGBinaryWrapper($this->binary, $this->homeDir, sys_get_temp_dir());

        $result = $instance->import(file_get_contents(dirname(__DIR__) . '/fixtures/pkey.asc'));
        $this->assertIsArray($result);
    }

    public function testImportFailure(): void
    {
        $instance = new GnuPGBinaryWrapper($this->binary, $this->homeDir, sys_get_temp_dir());

        $result = $instance->import('FOO');
        $this->assertEquals(0, $result['imported']);
    }

    public function testKeyinfo(): void
    {
        $instance = new GnuPGBinaryWrapper($this->binary, $this->homeDir, sys_get_temp_dir());

        $instance->import(file_get_contents(dirname(__DIR__) . '/fixtures/pkey.asc'));
        $result = $instance->keyinfo('11BD3E86C5497BA1EB3B58B96DA9559564C3328F');

        $this->assertIsArray($result);
    }

    public function testVerify(): void
    {
        $instance = new GnuPGBinaryWrapper($this->binary, $this->homeDir, sys_get_temp_dir());

        $result = $instance->import(file_get_contents(dirname(__DIR__) . '/fixtures/pkey.asc'));
        $this->assertIsArray($result, 'Public key import failed');

        $result = $instance->verify(
            file_get_contents(dirname(__DIR__) . '/fixtures/test.txt'),
            file_get_contents(dirname(__DIR__) . '/fixtures/test.txt.asc')
        );

        $this->assertIsArray($result);
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
