<?php
declare(strict_types=1);

namespace App\Services\Sms\Providers;

use App\Helpers\SmsCredentialsStore;
use App\Services\Sms\Contracts\SmsSendResult;

/**
 * TurkeySMS REST API — sözleşme sonrası etkinleştirilir.
 */
final class TurkeySmsProvider extends AbstractHttpSmsProvider
{
    private const API_URL = 'https://turkeysms.com.tr/api/v4/send/';

    public function getCode(): string
    {
        return 'turkeysms';
    }

    public function getLabel(): string
    {
        return 'TurkeySMS';
    }

    protected function sendHttp(string $toNormalized, string $body, array $opts): SmsSendResult
    {
        $creds = SmsCredentialsStore::read();
        $payload = json_encode([
            'api_key' => $creds['api_key'],
            'sender' => $creds['sender_id'],
            'message' => $body,
            'numbers' => [$toNormalized],
        ], JSON_UNESCAPED_UNICODE);
        if (!is_string($payload)) {
            return SmsSendResult::fail('JSON', 'İstek oluşturulamadı');
        }
        $ch = curl_init(self::API_URL);
        if ($ch === false) {
            return SmsSendResult::fail('CURL', 'Bağlantı başlatılamadı');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($response) || $status < 200 || $status >= 300) {
            return SmsSendResult::fail('HTTP', 'TurkeySMS HTTP ' . $status);
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return SmsSendResult::fail('PARSE', 'Yanıt okunamadı');
        }
        if (($decoded['status'] ?? '') !== 'success' && ($decoded['success'] ?? false) !== true) {
            return SmsSendResult::fail('API', (string) ($decoded['message'] ?? 'Gönderim başarısız'));
        }
        $msgId = (string) ($decoded['data']['message_id'] ?? $decoded['id'] ?? bin2hex(random_bytes(6)));

        return SmsSendResult::ok($msgId);
    }
}
