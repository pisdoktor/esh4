<?php
declare(strict_types=1);

namespace App\Services\Sms\Contracts;

final class SmsSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $providerMessageId = '',
        public readonly string $errorCode = '',
        public readonly string $errorMessage = '',
        public readonly bool $skipped = false,
    ) {
    }

    public static function ok(string $msgId = ''): self
    {
        return new self(true, $msgId);
    }

    public static function fail(string $code, string $message): self
    {
        return new self(false, '', $code, $message);
    }

    public static function skip(string $reason): self
    {
        return new self(false, '', 'SKIP', $reason, true);
    }
}
