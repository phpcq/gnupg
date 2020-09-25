<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Test\stubs;

class Gnupg
{
    /** @SuppressWarnings(PHPMD) */
    public function import(string $key)
    {
    }

    /** @SuppressWarnings(PHPMD) */
    public function verify(string $message, $signature)
    {
    }

    /** @SuppressWarnings(PHPMD) */
    public function keyinfo(string $key)
    {
    }
}
