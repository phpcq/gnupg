<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Signature;

final class AlwaysStrategy implements TrustKeyStrategyInterface
{
    /** @var bool */
    private $trust;

    public function __construct(bool $trust)
    {
        $this->trust = $trust;
    }

    /** @SuppressWarnings(PHPMD.CamelCaseMethodName) */
    public static function TRUST(): self
    {
        return new self(true);
    }

    /** @SuppressWarnings(PHPMD.CamelCaseMethodName) */
    public static function REJECT(): self
    {
        return new self(false);
    }

    public function isTrusted(string $fingerprint) : bool
    {
        return $this->trust;
    }
}
