<?php

declare(strict_types=1);

namespace Phpcq\GnuPG;

use Gnupg;
use Phpcq\GnuPG\Exception\RuntimeException;
use Phpcq\GnuPG\Wrapper\GnuPGBinaryWrapper;
use Phpcq\GnuPG\Wrapper\GnuPGExtensionWrapper;

use function putenv;
use function sprintf;

final class GnuPGFactory
{
    /** @var string */
    private $tempDirectory;

    public function __construct(string $tempDirectory)
    {
        $this->tempDirectory = $tempDirectory;
    }

    public function create(string $homeDirectory): GnuPGInterface
    {
        try {
            return $this->createExtensionWrapper($homeDirectory);
        } catch (RuntimeException $exception) {
            // Failed. Try the binary now.
        }

        try {
            return $this->createBinaryWrapper($homeDirectory);
        } catch (RuntimeException $exception) {
            throw new RuntimeException(sprintf('Neighter gnugp extension loaded nor gpg binary found.'));
        }
    }

    public function createExtensionWrapper(string $homeDirectory): GnuPGInterface
    {
        if (! $this->isExtensionAvailable()) {
            throw new RuntimeException(
                'Instantiating gnupg extension wrapper failed. Gnupg extension is not available.'
            );
        }

        putenv('GNUPGHOME=' . $homeDirectory);

        /**
         * @psalm-suppress MixedAssignment
         * @psalm-suppress UndefinedClass - The gnupg extension might not be loaded
         */
        $gpg = new Gnupg();
        $gpg->seterrormode(Gnupg::ERROR_EXCEPTION);

        return new GnuPGExtensionWrapper($gpg);
    }

    public function createBinaryWrapper(string $homeDirectory, ?string $gppBinary = null): GnuPGInterface
    {
        $gpgBinary = $gppBinary ?: $this->findBinary();
        if (null === $gpgBinary) {
            throw new RuntimeException('Instantiating gnupg binary wrapper failed. Gnupg binary not found');
        }

        return new GnuPGBinaryWrapper($gpgBinary, $homeDirectory, $this->tempDirectory);
    }

    private function isExtensionAvailable(): bool
    {
        return extension_loaded('gnupg');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.UndefinedVariable)
     */
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
