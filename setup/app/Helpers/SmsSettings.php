<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * SMS modülü operasyonel ayarları (OperationalSettings sms bölümü + credential store).
 */
final class SmsSettings
{
    /** @var list<string> */
    public const PROVIDERS = ['mock', 'netgsm', 'iletimerkezi', 'turkeysms', 'custom_http'];

    /** @var list<string> */
    public const ROLES = ['hasta', 'hasta2', 'bakimveren', 'ailehekimi'];

    /** @var list<string> */
    public const SEGMENTS = [
        'tek_hasta',
        'coklu_hasta',
        'gunun_plani',
        'pansuman_bugun',
        'pansuman_liste',
        'sonda_yaklasan',
        'planli_izlem',
        'ilk_ziyaret',
        'bekleyen_kayit',
    ];

    public static function isEnabled(): bool
    {
        return AppSettings::isModuleEnabled('sms_bildirim');
    }

    public static function testMode(): bool
    {
        return OperationalSettings::bool('sms', 'test_mode', true);
    }

    public static function dailyLimit(): int
    {
        return max(1, OperationalSettings::int('sms', 'daily_limit', 500));
    }

    public static function kvkkMetni(): string
    {
        return OperationalSettings::string('sms', 'kvkk_metni', 'Telefon numaranız evde sağlık hizmeti bilgilendirmeleri için kullanılacaktır.');
    }

    public static function activeProvider(): string
    {
        $fromCreds = SmsCredentialsStore::read()['provider'];
        if ($fromCreds !== '') {
            return $fromCreds;
        }

        return OperationalSettings::string('sms', 'provider', 'mock');
    }

    public static function senderId(): string
    {
        return SmsCredentialsStore::read()['sender_id'];
    }

    /**
     * @return list<string>
     */
    public static function defaultRoles(): array
    {
        $csv = OperationalSettings::string('sms', 'default_roles', 'bakimveren,hasta');
        $parts = array_filter(array_map('trim', explode(',', $csv)));
        $valid = array_values(array_intersect($parts, self::ROLES));

        return $valid !== [] ? $valid : ['bakimveren'];
    }

    public static function providerLabel(string $code): string
    {
        return match ($code) {
            'netgsm' => 'Netgsm',
            'iletimerkezi' => 'İletiMerkezi',
            'turkeysms' => 'TurkeySMS',
            'custom_http' => 'Özel HTTP API',
            default => 'Test (Mock)',
        };
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'hasta' => 'Hasta (cep 1)',
            'hasta2' => 'Hasta (cep 2)',
            'bakimveren' => 'Bakım veren / yakın',
            'ailehekimi' => 'Aile hekimi',
            default => $role,
        };
    }

    public static function segmentLabel(string $segment): string
    {
        return match ($segment) {
            'tek_hasta' => 'Tek hasta',
            'coklu_hasta' => 'Çoklu hasta seçimi',
            'gunun_plani' => 'Günün planı',
            'pansuman_bugun' => 'Bugün pansuman günü',
            'pansuman_liste' => 'Pansuman listesi',
            'sonda_yaklasan' => 'Sonda değişimi yaklaşanlar',
            'planli_izlem' => 'Planlı izlem',
            'ilk_ziyaret' => 'İlk ziyaret randevusu',
            'bekleyen_kayit' => 'Bekleyen kayıtlar',
            default => $segment,
        };
    }
}
