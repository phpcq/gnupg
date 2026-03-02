<?php

declare(strict_types=1);

namespace Phpcq\GnuPG\Signature;

use Phpcq\GnuPG\Downloader\KeyDownloader;
use Phpcq\GnuPG\GnuPGInterface;

/** @api */
final class SignatureVerifier
{
    /** @var GnuPGInterface */
    private $gnupg;

    /** @var KeyDownloader */
    private $keyDownloader;

    /**
     * @var TrustKeyStrategyInterface
     */
    private $trustKeyStrategy;

    public function __construct(
        GnuPGInterface $gnupg,
        KeyDownloader $keyDownloader,
        ?TrustKeyStrategyInterface $unknownKeyStrategy = null
    ) {
        $this->gnupg            = $gnupg;
        $this->keyDownloader    = $keyDownloader;
        $this->trustKeyStrategy = $unknownKeyStrategy ?: AlwaysStrategy::REJECT();
    }

    public function verify(string $content, string $signature): VerificationResult
    {
        $result = $this->doVerify($content, $signature);
        if ($result->isValid() || $result->isUnknownError()) {
            return $result;
        }

        $fingerprint = $result->getFingerprint();
        if (null === $fingerprint) {
            return VerificationResult::UNKOWN_ERROR();
        }

        if (!$this->isTrusted($fingerprint)) {
            return VerificationResult::UNTRUSTED_KEY($fingerprint);
        }

        $key = $this->keyDownloader->downloadKey($fingerprint);
        $this->gnupg->import($key);

        return $this->doVerify($content, $signature);
    }

    private function doVerify(string $content, string $signature): VerificationResult
    {
        $result = $this->gnupg->verify($content, $signature);

        if ($result === false || !isset($result[0]['fingerprint']) || !isset($result[0]['summary'])) {
            return VerificationResult::UNKOWN_ERROR();
        }

        $fingerprint = $result[0]['fingerprint'];
        if (($result[0]['summary'] & 128) === 128) {
            return VerificationResult::UNTRUSTED_KEY($fingerprint);
        }

        return VerificationResult::VALID($fingerprint);
    }

    private function isTrusted(string $fingerprint): bool
    {
        if ($this->trustKeyStrategy->isTrusted($fingerprint)) {
            return true;
        }
        if (16 < strlen($fingerprint)) {
            return $this->trustKeyStrategy->isTrusted(substr($fingerprint, -16));
        }
        return false;
    }
}
