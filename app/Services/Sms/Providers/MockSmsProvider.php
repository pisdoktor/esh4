<?php
declare(strict_types=1);

namespace App\Services\Sms\Providers;

use App\Helpers\SmsCredentialsStore;
use App\Services\Sms\Contracts\SmsProviderInterface;
use App\Services\Sms\Contracts\SmsSendResult;

final class MockSmsProvider implements SmsProviderInterface
{
    public function getCode(): string
    {
        return 'mock';
    }

    public function getLabel(): string
    {
        return 'Test (Mock)';
    }

    public function send(string $toNormalized, string $body, array $opts = []): SmsSendResult
    {
        if ($toNormalized === '') {
            return SmsSendResult::fail('INVALID', 'Telefon boş');
        }
        $msgId = 'mock-' . bin2hex(random_bytes(8));
        $this->log($toNormalized, $body, $msgId);

        return SmsSendResult::ok($msgId);
    }

    public function testConnection(string $testPhone, string $body = 'ESH SMS test mesaji'): SmsSendResult
    {
        $norm = \App\Services\Sms\SmsPhoneNormalizer::normalize($testPhone);
        if ($norm === null) {
            return SmsSendResult::fail('INVALID', 'Geçersiz test telefonu');
        }

        return $this->send($norm, $body, ['test' => true]);
    }

    private function log(string $to, string $body, string $msgId): void
    {
        $dir = rtrim((string) ROOT_PATH, '/\\') . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = date('c') . "\t{$msgId}\t{$to}\t" . str_replace(["\r", "\n"], ' ', $body) . "\n";
        @file_put_contents($dir . '/sms_mock.log', $line, FILE_APPEND | LOCK_EX);
    }
}
