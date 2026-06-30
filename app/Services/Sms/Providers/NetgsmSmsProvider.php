<?php
declare(strict_types=1);

namespace App\Services\Sms\Providers;

use App\Helpers\SmsCredentialsStore;
use App\Services\Sms\Contracts\SmsSendResult;
use App\Services\Sms\SmsPhoneNormalizer;

/**
 * Netgsm HTTP GET/XML API — sözleşme sonrası etkinleştirilir.
 */
final class NetgsmSmsProvider extends AbstractHttpSmsProvider
{
    private const API_URL = 'https://api.netgsm.com.tr/sms/send/get/';

    public function getCode(): string
    {
        return 'netgsm';
    }

    public function getLabel(): string
    {
        return 'Netgsm';
    }

    protected function sendHttp(string $toNormalized, string $body, array $opts): SmsSendResult
    {
        $creds = SmsCredentialsStore::read();
        $to = SmsPhoneNormalizer::toDisplay($toNormalized);
        $params = http_build_query([
            'usercode' => $creds['api_user'],
            'password' => $creds['api_password'],
            'gsmno' => $to,
            'message' => $body,
            'msgheader' => $creds['sender_id'],
            'dil' => 'TR',
        ]);
        $res = $this->httpPost(self::API_URL . '?' . $params, '');
        if (!$res['ok']) {
            return SmsSendResult::fail('HTTP', 'Netgsm HTTP ' . ($res['status'] ?? 0) . ': ' . ($res['error'] ?: substr((string) $res['body'], 0, 200)));
        }
        $raw = trim((string) $res['body']);
        if ($raw === '' || str_starts_with($raw, '00') === false && !ctype_digit(substr($raw, 0, 2))) {
            if (preg_match('/^(\d+)\s/', $raw, $m) && (int) $m[1] > 100) {
                return SmsSendResult::ok(trim($raw));
            }

            return SmsSendResult::fail('NETGSM', $raw !== '' ? $raw : 'Bilinmeyen yanıt');
        }

        return SmsSendResult::ok($raw);
    }
}
