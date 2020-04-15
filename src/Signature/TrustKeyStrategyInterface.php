<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Signature;

interface TrustKeyStrategyInterface
{
    public function isTrusted(string $fingerprint) : bool;
}
