<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Signature;

use function in_array;

final class TrustedKeysStrategy implements TrustKeyStrategyInterface
{
    /**
     * @param-var list<string>
     *
     * @var string[]
     */
    private $trustedKeys;

    /**
     * @param-param list<string> $trustedKeys
     *
     * @param string[] $trustedKeys
     */
    public function __construct(array $trustedKeys)
    {
        $this->trustedKeys = $trustedKeys;
    }

    public function isTrusted(string $fingerprint) : bool
    {
        return in_array($fingerprint, $this->trustedKeys, true);
    }
}
