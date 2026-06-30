<?php
declare(strict_types=1);

namespace App\Services\Sms\Providers;

use App\Helpers\SmsCredentialsStore;
use App\Services\Sms\Contracts\SmsSendResult;
use App\Services\Sms\SmsPhoneNormalizer;

/**
 * İletiMerkezi JSON API — sözleşme sonrası etkinleştirilir.
 */
final class IletimerkeziSmsProvider extends AbstractHttpSmsProvider
{
    private const API_URL = 'https://api.iletimerkezi.com/v1/send-sms/json';

    public function getCode(): string
    {
        return 'iletimerkezi';
    }

    public function getLabel(): string
    {
        return 'İletiMerkezi';
    }

    protected function sendHttp(string $toNormalized, string $body, array $opts): SmsSendResult
    {
        $creds = SmsCredentialsStore::read();
        $payload = json_encode([
            'request' => [
                'authentication' => [
                    'key' => $creds['api_user'],
                    'hash' => $creds['api_password'],
                ],
                'order' => [
                    'sender' => $creds['sender_id'],
                    'message' => [
                        'text' => $body,
                        'receipents' => [
                            'number' => [SmsPhoneNormalizer::toDisplay($toNormalized)],
                        ],
                    ],
                ],
            ],
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
            return SmsSendResult::fail('HTTP', 'İletiMerkezi HTTP ' . $status);
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return SmsSendResult::fail('PARSE', 'Yanıt okunamadı');
        }
        $code = (string) ($decoded['response']['status']['code'] ?? '');
        if ($code !== '' && $code !== '200' && $code !== '110') {
            $msg = (string) ($decoded['response']['status']['message'] ?? 'Hata');

            return SmsSendResult::fail($code, $msg);
        }
        $orderId = (string) ($decoded['response']['order']['id'] ?? bin2hex(random_bytes(6)));

        return SmsSendResult::ok($orderId);
    }
}
