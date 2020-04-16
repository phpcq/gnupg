<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Wrapper;

use Gnupg;
use Phpcq\GnuPG\Exception\Exception;
use Phpcq\GnuPG\GnuPGInterface;

final class GnuPGExtensionWrapper implements GnuPGInterface
{
    /** @var Gnupg */
    private $inner;

    /**
     * GnuPGDecorator constructor.
     *
     * @param Gnupg $inner
     */
    public function __construct(Gnupg $inner)
    {
        $this->inner = $inner;
    }

    /** @inheritDoc */
    public function import(string $key) : array
    {
        $result = $this->inner->import($key);
        if ($result === false) {
            return ['imported' => 0];
        }

        return $result;
    }

    /** @inheritDoc */
    public function keyinfo(string $search) : array
    {
        return $this->inner->keyinfo($search);
    }

    /** @inheritDoc */
    public function verify(string $message, ?string $signature = null)
    {
        return $this->inner->verify($message, $signature);
    }
}
