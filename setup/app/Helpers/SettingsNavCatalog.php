<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Ayar paneli sekme katalogu — tek kaynak (nav, erişim, alias, modül birleştirme).
 */
final class SettingsNavCatalog
{
    public const DEFAULT_TAB = 'modules';

    /** @var array<string, string> */
    public const TAB_ALIASES = [
        'guvenlik' => 'bakim',
    ];

    /** Modül anahtarı → ayar sekmesi (Modüller sekmesinde gösterilmez). */
    /** @var array<string, string> */
    public const MODULES_ON_DEDICATED_TABS = [
        'nobet' => 'nobet',
        'harita' => 'harita',
        'adres_koordinat' => 'harita',
        'manuel_koordinat' => 'harita',
        'uhds' => 'uhds',
        'sms_bildirim' => 'sms',
        'kps_tc_sorgu' => 'kps',
        'eimza_login' => 'eimza',
        'patient_portal' => 'misafir',
        'modern_frontend' => 'gelismis',
        'rest_api' => 'gelismis',
    ];

    /** @var array<string, string> */
    public const CATEGORY_LABELS = [
        'overview' => 'Genel',
        'kurum' => 'Kurum işletimi',
        'saha' => 'Saha ve planlama',
        'iletisim' => 'İletişim ve kimlik',
        'platform' => 'Platform',
    ];

    /** @var list<string> */
    public const CATEGORY_ORDER = [
        'overview',
        'kurum',
        'saha',
        'iletisim',
        'platform',
    ];

    /** @var list<string> */
    public const KURUM_SCOPED_TABS = [
        'modules',
        'islem_ids',
        'kurumsal',
        'nobet',
        'harita',
        'vardiya',
        'planlama',
        'saha',
        'uhds',
        'sms',
    ];

    /** @var list<string> */
    public const PLATFORM_ONLY_TABS = [
        'bakim',
        'misafir',
        'entegrasyon',
        'gelismis',
        'kps',
        'eimza',
    ];

    /** @var list<string> */
    private const ALL_TABS = [
        'overview',
        'modules',
        'islem_ids',
        'kurumsal',
        'nobet',
        'harita',
        'vardiya',
        'planlama',
        'saha',
        'uhds',
        'sms',
        'bakim',
        'misafir',
        'entegrasyon',
        'gelismis',
        'kps',
        'eimza',
    ];

    /** @var array<string, array{label:string,icon:string,category:string,scope:string,description:string,read_only?:bool}> */
    private const TAB_META = [
        'overview' => [
            'label' => 'Genel bakış',
            'icon' => 'fa-gauge-high',
            'category' => 'overview',
            'scope' => 'all',
            'description' => 'Ayar kategorilerine hızlı erişim ve durum özeti.',
            'read_only' => true,
        ],
        'modules' => [
            'label' => 'Uygulama modülleri',
            'icon' => 'fa-toggle-on',
            'category' => 'kurum',
            'scope' => 'kurum',
            'description' => 'Kurum için açık/kapalı modüller.',
        ],
        'islem_ids' => [
            'label' => "İşlem id'leri",
            'icon' => 'fa-list-ol',
            'category' => 'kurum',
            'scope' => 'kurum',
            'description' => 'İzlem formlarında kullanılan işlem tanımları.',
        ],
        'kurumsal' => [
            'label' => 'Kurumsal metinler',
            'icon' => 'fa-building',
            'category' => 'kurum',
            'scope' => 'kurum',
            'description' => 'Kurum adı ve form başlıkları.',
        ],
        'nobet' => [
            'label' => 'Nöbet',
            'icon' => 'fa-calendar-week',
            'category' => 'kurum',
            'scope' => 'kurum',
            'description' => 'Nöbet ünvanları ve günlük slot sayıları.',
        ],
        'harita' => [
            'label' => 'Harita ve koordinat',
            'icon' => 'fa-map-location-dot',
            'category' => 'saha',
            'scope' => 'kurum',
            'description' => 'Harita merkezi ve sağlayıcı durumu.',
        ],
        'vardiya' => [
            'label' => 'Zaman dilimleri',
            'icon' => 'fa-clock',
            'category' => 'saha',
            'scope' => 'kurum',
            'description' => 'Sabah, öğle, akşam vardiya saatleri.',
        ],
        'planlama' => [
            'label' => 'Rota ve planlama',
            'icon' => 'fa-route',
            'category' => 'saha',
            'scope' => 'kurum',
            'description' => 'Rota skor parametreleri ve izlem süre varsayılanları.',
        ],
        'saha' => [
            'label' => 'Saha ziyareti',
            'icon' => 'fa-mobile-screen-button',
            'category' => 'saha',
            'scope' => 'kurum',
            'description' => 'Mobil GPS ve çevrimdışı plan önbelleği.',
        ],
        'uhds' => [
            'label' => 'UHDS görüşme',
            'icon' => 'fa-video',
            'category' => 'saha',
            'scope' => 'kurum',
            'description' => 'Jitsi tabanlı görüntülü görüşme ayarları.',
        ],
        'sms' => [
            'label' => 'SMS ve bildirimler',
            'icon' => 'fa-comment-sms',
            'category' => 'iletisim',
            'scope' => 'kurum',
            'description' => 'SMS modülü, operasyonel ayarlar ve sağlayıcı kimlik bilgileri.',
        ],
        'bakim' => [
            'label' => 'Bakım ve sistem',
            'icon' => 'fa-screwdriver-wrench',
            'category' => 'platform',
            'scope' => 'platform',
            'description' => 'Bakım modu ve hata ayıklama seçenekleri.',
        ],
        'misafir' => [
            'label' => 'Misafir erişimi',
            'icon' => 'fa-house-chimney-user',
            'category' => 'platform',
            'scope' => 'platform',
            'description' => 'Kamu TC sorgusu ve hasta portalı.',
        ],
        'entegrasyon' => [
            'label' => 'Dış sistem köprüleri',
            'icon' => 'fa-bridge',
            'category' => 'platform',
            'scope' => 'platform',
            'description' => 'ESYS, USBS ve federasyon ayarları.',
        ],
        'gelismis' => [
            'label' => 'Gelişmiş özellikler',
            'icon' => 'fa-wand-magic-sparkles',
            'category' => 'platform',
            'scope' => 'platform',
            'description' => 'Modern frontend, klinik karar desteği ve denetim günlüğü.',
        ],
        'eimza' => [
            'label' => 'E-imza',
            'icon' => 'fa-file-signature',
            'category' => 'platform',
            'scope' => 'platform',
            'description' => 'E-imza giriş modülü ve yapılandırma durumu.',
        ],
        'kps' => [
            'label' => 'KPS kimlik doğrulama',
            'icon' => 'fa-id-card',
            'category' => 'iletisim',
            'scope' => 'platform',
            'description' => 'KPS modülü, servis ayarları ve kimlik bilgileri.',
        ],
    ];

    /** @var list<string> */
    public const MODULE_GROUP_ORDER = ['core', 'site', 'auth', 'admin', 'public'];

    /** @var list<string> */
    public const KURUM_SCOPE_BANNER_TABS = [
        'modules',
        'islem_ids',
        'harita',
        'vardiya',
        'planlama',
        'saha',
        'uhds',
        'kurumsal',
        'nobet',
    ];

    public static function resolveTab(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return self::DEFAULT_TAB;
        }
        if (isset(self::TAB_ALIASES[$raw])) {
            return self::TAB_ALIASES[$raw];
        }
        if (in_array($raw, self::ALL_TABS, true)) {
            return $raw;
        }

        return self::DEFAULT_TAB;
    }

    public static function isAliasRedirect(string $raw): bool
    {
        $raw = trim($raw);

        return $raw !== '' && isset(self::TAB_ALIASES[$raw]) && self::TAB_ALIASES[$raw] !== $raw;
    }

    /** @return list<string> */
    public static function allTabKeys(): array
    {
        return self::ALL_TABS;
    }

    /** @return list<string> */
    public static function tabsForRole(): array
    {
        $tabs = self::KURUM_SCOPED_TABS;
        if (AuthHelper::sessionIsPlatformOwner()) {
            return array_merge(['overview'], $tabs, self::PLATFORM_ONLY_TABS);
        }

        return $tabs;
    }

    /**
     * @return array<string, list<array{key:string,label:string,icon:string,description:string,badge?:string}>>
     */
    public static function navGroupedForRole(): array
    {
        $allowed = self::tabsForRole();
        $grouped = [];
        foreach (self::CATEGORY_ORDER as $category) {
            $items = [];
            foreach (self::TAB_META as $key => $meta) {
                if (($meta['category'] ?? '') !== $category) {
                    continue;
                }
                if (!in_array($key, $allowed, true)) {
                    continue;
                }
                $badge = self::statusBadgeForTab($key);
                $item = [
                    'key' => $key,
                    'label' => (string) ($meta['label'] ?? $key),
                    'icon' => (string) ($meta['icon'] ?? 'fa-circle'),
                    'description' => (string) ($meta['description'] ?? ''),
                ];
                if ($badge !== null && $badge !== '') {
                    $item['badge'] = $badge;
                }
                $items[] = $item;
            }
            if ($items !== []) {
                $grouped[$category] = $items;
            }
        }

        return $grouped;
    }

    /**
     * @return array{label:string,icon:string,category:string,scope:string,description:string,read_only?:bool}|null
     */
    public static function tabMeta(string $tab): ?array
    {
        $tab = self::resolveTab($tab);

        return self::TAB_META[$tab] ?? null;
    }

    public static function tabLabel(string $tab): string
    {
        $meta = self::tabMeta($tab);

        return $meta !== null ? (string) ($meta['label'] ?? $tab) : $tab;
    }

    public static function isReadOnlyTab(string $tab): bool
    {
        $meta = self::tabMeta($tab);

        return $meta !== null && !empty($meta['read_only']);
    }

    public static function moduleKeyForTab(string $tab): ?string
    {
        foreach (self::MODULES_ON_DEDICATED_TABS as $moduleKey => $dedicatedTab) {
            if ($dedicatedTab === $tab) {
                return $moduleKey;
            }
        }

        return null;
    }

    public static function isModuleOnDedicatedTab(string $moduleKey): bool
    {
        return isset(self::MODULES_ON_DEDICATED_TABS[$moduleKey]);
    }

    public static function dedicatedTabForModule(string $moduleKey): ?string
    {
        return self::MODULES_ON_DEDICATED_TABS[$moduleKey] ?? null;
    }

    /** @return list<string> */
    public static function dedicatedModulesForTab(string $tab): array
    {
        $tab = self::resolveTab($tab);
        $keys = [];
        foreach (self::MODULES_ON_DEDICATED_TABS as $moduleKey => $dedicatedTab) {
            if ($dedicatedTab === $tab) {
                $keys[] = $moduleKey;
            }
        }

        return $keys;
    }

    public static function tabHasDedicatedModules(string $tab): bool
    {
        return self::dedicatedModulesForTab($tab) !== [];
    }

    /**
     * @return list<array{key:string,label:string,icon:string,description:string,href:string,badge?:string}>
     */
    public static function overviewCardsForRole(): array
    {
        $allowed = self::tabsForRole();
        $cards = [];
        foreach (self::TAB_META as $key => $meta) {
            if ($key === 'overview') {
                continue;
            }
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $badge = self::statusBadgeForTab($key);
            $card = [
                'key' => $key,
                'label' => (string) ($meta['label'] ?? $key),
                'icon' => (string) ($meta['icon'] ?? 'fa-circle'),
                'description' => (string) ($meta['description'] ?? ''),
                'href' => self::tabUrl($key),
                'category' => (string) ($meta['category'] ?? ''),
            ];
            if ($badge !== null && $badge !== '') {
                $card['badge'] = $badge;
            }
            $cards[] = $card;
        }

        return $cards;
    }

    public static function tabUrl(string $tab): string
    {
        $tab = self::resolveTab($tab);
        if ($tab === self::DEFAULT_TAB) {
            return esh_url('Settings', 'index');
        }

        return esh_url('Settings', 'index', ['tab' => $tab]);
    }

    public static function statusBadgeForTab(string $tab): ?string
    {
        $tab = self::resolveTab($tab);
        try {
            return match ($tab) {
                'bakim' => OperationalSettings::bool('maintenance', 'enabled', false) ? 'Bakım açık' : null,
                'sms' => \App\Helpers\SmsCredentialsStore::statusForAdmin()['configured'] ?? false ? 'SMS yapılandırıldı' : null,
                'kps' => (\App\Helpers\KpsCredentialsStore::statusForAdmin()['configured'] ?? false) ? 'KPS yapılandırıldı' : null,
                'misafir' => AppSettings::isModuleEnabled('patient_portal') ? 'Portal açık' : null,
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }
}
