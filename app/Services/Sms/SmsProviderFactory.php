<?php
declare(strict_types=1);

namespace App\Services\Sms;

use App\Helpers\SmsCredentialsStore;
use App\Helpers\SmsSettings;
use App\Services\Sms\Contracts\SmsProviderInterface;
use App\Services\Sms\Providers\IletimerkeziSmsProvider;
use App\Services\Sms\Providers\MockSmsProvider;
use App\Services\Sms\Providers\NetgsmSmsProvider;

final class SmsProviderFactory
{
    public static function create(?string $code = null): SmsProviderInterface
    {
        $code = $code ?? SmsSettings::activeProvider();
        if (SmsSettings::testMode()) {
            return new MockSmsProvider();
        }

        return match ($code) {
            'netgsm' => new NetgsmSmsProvider(),
            'iletimerkezi' => new IletimerkeziSmsProvider(),
            'turkeysms' => new Providers\TurkeySmsProvider(),
            'custom_http' => new Providers\CustomHttpSmsProvider(),
            default => new MockSmsProvider(),
        };
    }

    public static function isReady(): bool
    {
        $code = SmsSettings::activeProvider();
        if ($code === 'mock' || SmsSettings::testMode()) {
            return true;
        }

        return SmsCredentialsStore::isConfiguredForProvider($code);
    }
}
