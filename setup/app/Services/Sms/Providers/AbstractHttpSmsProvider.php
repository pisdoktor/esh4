<?php
declare(strict_types=1);

namespace App\Services\Sms\Providers;

use App\Helpers\SmsCredentialsStore;
use App\Helpers\SmsSettings;
use App\Services\Sms\Contracts\SmsProviderInterface;
use App\Services\Sms\Contracts\SmsSendResult;

abstract class AbstractHttpSmsProvider implements SmsProviderInterface
{
    protected const TIMEOUT = 15;

    /**
     * @param array<string, mixed> $opts
     */
    public function send(string $toNormalized, string $body, array $opts = []): SmsSendResult
    {
        if (SmsSettings::testMode()) {
            return (new MockSmsProvider())->send($toNormalized, $body, $opts);
        }
        if (!SmsCredentialsStore::isConfiguredForProvider($this->getCode())) {
            return SmsSendResult::fail('CONFIG', 'Sağlayıcı yapılandırması eksik');
        }

        return $this->sendHttp($toNormalized, $body, $opts);
    }

    public function testConnection(string $testPhone, string $body = 'ESH SMS test mesaji'): SmsSendResult
    {
        $norm = \App\Services\Sms\SmsPhoneNormalizer::normalize($testPhone);
        if ($norm === null) {
            $norm = \App\Services\Sms\SmsPhoneNormalizer::normalize(SmsCredentialsStore::read()['test_phone']);
        }
        if ($norm === null) {
            return SmsSendResult::fail('INVALID', 'Test telefonu tanımlı değil');
        }

        return $this->send($norm, $body, ['test' => true]);
    }

    /**
     * @param array<string, mixed> $opts
     */
    abstract protected function sendHttp(string $toNormalized, string $body, array $opts): SmsSendResult;

    /**
     * @param array<string, string> $headers
     */
    protected function httpPost(string $url, string $body, array $headers = []): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'curl_init failed'];
        }
        $defaultHeaders = ['Content-Type: application/x-www-form-urlencoded'];
        foreach ($headers as $k => $v) {
            $defaultHeaders[] = $k . ': ' . $v;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_HTTPHEADER => $defaultHeaders,
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return [
            'ok' => $response !== false && $status >= 200 && $status < 300,
            'status' => $status,
            'body' => is_string($response) ? $response : '',
            'error' => $err,
        ];
    }
}
