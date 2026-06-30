<?php
declare(strict_types=1);

namespace App\Services\Sms\Providers;

use App\Helpers\SmsCredentialsStore;
use App\Services\Sms\Contracts\SmsSendResult;

/**
 * Özel HTTP endpoint — config.local.php sms_custom_api_url ile tanımlanır.
 */
final class CustomHttpSmsProvider extends AbstractHttpSmsProvider
{
    public function getCode(): string
    {
        return 'custom_http';
    }

    public function getLabel(): string
    {
        return 'Özel HTTP API';
    }

    protected function sendHttp(string $toNormalized, string $body, array $opts): SmsSendResult
    {
        $url = defined('SMS_CUSTOM_API_URL') ? (string) SMS_CUSTOM_API_URL : '';
        if ($url === '') {
            return SmsSendResult::fail('CONFIG', 'SMS_CUSTOM_API_URL tanımlı değil (config.local.php)');
        }
        $creds = SmsCredentialsStore::read();
        $payload = json_encode([
            'to' => $toNormalized,
            'message' => $body,
            'sender' => $creds['sender_id'],
            'api_key' => $creds['api_key'],
        ], JSON_UNESCAPED_UNICODE);
        if (!is_string($payload)) {
            return SmsSendResult::fail('JSON', 'İstek oluşturulamadı');
        }
        $ch = curl_init($url);
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
            return SmsSendResult::fail('HTTP', 'Özel API HTTP ' . $status);
        }

        return SmsSendResult::ok('custom-' . substr(hash('sha256', $toNormalized . $body), 0, 12));
    }
}
