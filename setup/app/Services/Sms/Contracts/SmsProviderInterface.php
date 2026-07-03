<?php
declare(strict_types=1);

namespace App\Services\Sms\Contracts;

interface SmsProviderInterface
{
    public function getCode(): string;

    public function getLabel(): string;

    /**
     * @param array<string, mixed> $opts sender_id, mesaj_turu vb.
     */
    public function send(string $toNormalized, string $body, array $opts = []): SmsSendResult;

    public function testConnection(string $testPhone, string $body = 'ESH SMS test mesaji'): SmsSendResult;
}
