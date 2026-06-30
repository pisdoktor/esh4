<?php
namespace App\Helpers;

/**
 * Tema editörü — `--esh-ui-*` jeton grupları ve kısa kullanım etiketleri.
 * Teknik ad ilk sütunda; bu metinler «nerede / ne işe yarar» sütunundadır.
 *
 * @see public/assets/esh-page-language.css
 * @see docs/ESH_PAGE_LANGUAGE.md
 */
final class EshUiTokenCatalog
{
    /**
     * @var array<string, array<string, string>> grup başlığı => [ jeton => kullanım açıklaması ]
     */
    public const GROUPS = [
        'Genel sayfa' => [
            '--esh-ui-text' => 'Tüm .esh-page sayfaları — gövde metni',
            '--esh-ui-text-muted' => 'Alt başlık, soluk açıklama, boş tablo metni',
            '--esh-ui-accent' => 'Sayfa başlığı (.esh-page__heading), link ve .text-primary',
            '--esh-ui-surface' => 'Kart / panel yüzeyi (.esh-page__panel, .card)',
            '--esh-ui-surface-muted' => 'Filtre paneli, ikincil kart gövdesi (bg-body-tertiary yerine)',
            '--esh-ui-border' => 'Kart, panel, tablo ve kesik çizgi kenarlıkları',
            '--esh-ui-radius' => 'Kart ve panel köşe yuvarlaklığı',
            '--esh-ui-shadow' => 'Kart gölgesi (.esh-page__panel)',
            '--esh-ui-surface-subtle' => 'Açık zemin (.bg-light-50)',
            '--esh-ui-surface-warning' => 'Uyarılı açık zemin (.bg-light-warning)',
            '--esh-ui-soft-danger-bg' => 'Yönetim listesi — yumuşak kırmızı rozet zemin',
            '--esh-ui-soft-info-bg' => 'Yönetim listesi — yumuşak mavi rozet zemin',
            '--esh-ui-card-hover-shadow' => 'İstatistik kartları — hover gölge (.hover-shadow)',
            '--esh-ui-print-surface' => 'Haftalık plan — yazdırma sayfa zemin',
        ],
        'Tipografi (sayfa)' => [
            '--esh-ui-font-family' => 'Gövde yazı tipi (.esh-page)',
            '--esh-ui-font-size-base' => 'Gövde yazı boyutu',
            '--esh-ui-line-height' => 'Gövde satır yüksekliği',
            '--esh-ui-heading-size' => 'Sayfa başlığı (.esh-page__heading)',
            '--esh-ui-lead-size' => 'Lead / alt açıklama (.esh-page__lead)',
            '--esh-ui-font-weight-heading' => 'Sayfa başlığı kalınlığı',
            '--esh-ui-panel-subtitle-size' => 'Panel alt başlık (.esh-page__panel-subtitle)',
            '--esh-ui-form-control-font-size' => 'Form alanı — input/select/Tom Select (.esh-page)',
            '--esh-ui-form-control-sm-font-size' => 'Form alanı — küçük varyant (-sm)',
            '--esh-ui-form-control-lg-font-size' => 'Form alanı — büyük varyant (-lg)',
        ],
        'Tablolar ve hasta listeleri' => [
            '--esh-ui-table-head-bg' => 'Tablo thead — arka plan (liste sayfaları)',
            '--esh-ui-table-head-color' => 'Tablo thead — metin rengi',
            '--esh-ui-table-head-font-size' => 'Tablo thead — yazı boyutu (genel)',
            '--esh-ui-table-head-letter-spacing' => 'Tablo thead — harf aralığı',
            '--esh-ui-table-head-padding' => 'Tablo thead — hücre iç boşluğu',
            '--esh-ui-row-hover' => 'Tablo satırı üzerine gelince zemin',
            '--esh-ui-font-size-xs' => 'Hasta listesi — .x-small metin',
            '--esh-ui-font-size-sm' => 'Hasta listesi — .small ve dropdown öğe',
            '--esh-ui-font-size-badge' => 'Hasta listesi — satır rozetleri',
            '--esh-ui-dropdown-font-size' => 'Satır işlem menüsü — öğe boyutu',
            '--esh-ui-dropdown-header-font-size' => 'Dropdown grup başlığı',
            '--esh-ui-dropdown-header-letter-spacing' => 'Dropdown grup başlığı aralığı',
            '--esh-ui-dropdown-menu-radius' => 'Satır dropdown — köşe',
            '--esh-ui-dropdown-menu-border' => 'Satır dropdown — kenarlık',
            '--esh-ui-dropdown-item-hover-bg' => 'Satır dropdown — öğe hover',
            '--esh-ui-btn-ghost-bg' => 'Yönetim hasta listesi — .btn-white zemin',
            '--esh-ui-btn-ghost-border' => 'Yönetim hasta listesi — .btn-white kenarlık',
            '--esh-ui-btn-ghost-hover-bg' => 'Yönetim hasta listesi — .btn-white hover zemin',
            '--esh-ui-btn-ghost-hover-color' => 'Yönetim hasta listesi — .btn-white hover metin',
        ],
        'Filtre satırı' => [
            '--esh-ui-filter-control-height' => 'Filtre input/select — tek satır yükseklik (.esh-ui-filter-control)',
        ],
        'Uyarı mesajları (alert)' => [
            '--esh-ui-alert-success-bg' => 'Başarı uyarısı — zemin (.alert-success)',
            '--esh-ui-alert-success-color' => 'Başarı uyarısı — metin',
            '--esh-ui-alert-success-border' => 'Başarı uyarısı — kenarlık',
            '--esh-ui-alert-warning-bg' => 'Uyarı — zemin (.alert-warning)',
            '--esh-ui-alert-warning-color' => 'Uyarı — metin',
            '--esh-ui-alert-warning-border' => 'Uyarı — kenarlık',
            '--esh-ui-alert-danger-bg' => 'Hata uyarısı — zemin (.alert-danger)',
            '--esh-ui-alert-danger-color' => 'Hata uyarısı — metin',
            '--esh-ui-alert-danger-border' => 'Hata uyarısı — kenarlık',
            '--esh-ui-alert-info-bg' => 'Bilgi uyarısı — zemin (.alert-info)',
            '--esh-ui-alert-info-color' => 'Bilgi uyarısı — metin',
            '--esh-ui-alert-info-border' => 'Bilgi uyarısı — kenarlık',
        ],
        'Üst menü' => [
            '--esh-ui-navbar-link-font-size' => 'Navbar — tüm menü linkleri (.navbar .nav-link)',
            '--esh-ui-navbar-dropdown-font-size' => 'Navbar — açılır alt menü (.navbar .dropdown-menu)',
            '--esh-ui-navbar-brand-font-size' => 'Navbar — marka / logo metni (.navbar-brand)',
            '--esh-ui-navbar-bg' => 'Navbar — çubuk arka planı (varsayılan koyu tema)',
            '--esh-ui-navbar-link-color' => 'Navbar — ana link metin rengi',
            '--esh-ui-navbar-link-hover-color' => 'Navbar — ana link hover metni',
            '--esh-ui-navbar-link-active-color' => 'Navbar — ana link aktif / vurgu rengi',
            '--esh-ui-navbar-dropdown-shadow' => 'Navbar — açılır menü gölgesi',
            '--esh-ui-navbar-dropdown-item-hover-bg' => 'Navbar — menü öğesi hover zemin',
            '--esh-ui-navbar-dropdown-item-hover-color' => 'Navbar — menü öğesi hover metin',
        ],
        'Kabuk ve genel tablo' => [
            '--esh-ui-shell-font-family' => 'Site kabuğu — gövde yazı tipi (theme-default)',
            '--esh-ui-shell-bg' => 'Site kabuğu — sayfa arka planı (theme-default)',
            '--esh-ui-table-head-border-bottom' => 'Genel tablo thead — alt kenarlık',
            '--esh-ui-patient-row-hover-bg' => 'Hasta satırı (.patient-row) hover zemin',
            '--esh-ui-tc-code-bg' => 'TC kimlik kodu kutusu — zemin',
            '--esh-ui-tc-code-color' => 'TC kimlik kodu kutusu — metin',
            '--esh-ui-tc-code-font-size' => 'TC kimlik kodu — yazı boyutu',
            '--esh-ui-tc-code-radius' => 'TC kimlik kodu — köşe',
            '--esh-ui-tc-copy-flash-outline' => 'TC kopyalandı — kısa vurgu çerçevesi',
            '--esh-ui-status-badge-border' => 'Durum rozeti grubu — kenarlık rengi',
            '--esh-ui-status-badge-radius' => 'Durum rozeti grubu — köşe',
            '--esh-ui-status-badge-bg' => 'Durum rozeti grubu — zemin',
            '--esh-ui-btn-xs-font-size' => 'Çok küçük düğme (.btn-xs)',
            '--esh-ui-avatar-sm-size' => 'Küçük avatar — genişlik/yükseklik',
            '--esh-ui-avatar-sm-font-size' => 'Küçük avatar — baş harf boyutu',
            '--esh-ui-small-text-font-size' => 'Küçük yardımcı metin (.small-text)',
        ],
        'Dashboard' => [
            '--esh-ui-calendar-day-hover-bg' => 'Takvim — gün hücresi hover zemin',
            '--esh-ui-calendar-day-hover-ring' => 'Takvim — seçili gün iç çerçeve',
            '--esh-ui-cal-task-font-size' => 'Takvim — günlük görev etiketi',
            '--esh-ui-cal-badge-font-size' => 'Takvim — rozet ve ikon boyutu',
            '--esh-ui-cal-legend-font-size' => 'Takvim — alt açıklama rozeti',
            '--esh-ui-scrollbar-thumb' => 'Günün planı listesi — kaydırma çubuğu',
            '--esh-ui-scrollbar-radius' => 'Kaydırma çubuğu köşe',
            '--esh-ui-dash-plan-tabs-shadow' => 'Günün planı — yapışkan sekme gölgesi',
            '--esh-ui-daily-plan-badge-font-size' => 'Günlük plan satır kartı — rozet',
            '--esh-ui-daily-plan-meta-font-size' => 'Günlük plan satırı — tarih/saat metni',
            '--esh-ui-daily-section-font-size' => 'Dashboard — günlük bölüm başlığı',
        ],
        'Rota ve günün planı sekmeleri' => [
            '--esh-ui-route-tab-font-size' => 'Sabah/Öğle/Akşam sekmeleri — yazı (rota + dashboard)',
            '--esh-ui-route-tab-font-size-sm' => 'Mobil — sekme yazı boyutu',
            '--esh-ui-route-tab-font-weight' => 'Sekme etiketi kalınlığı',
            '--esh-ui-route-tab-font-weight-active' => 'Aktif sekme kalınlığı',
            '--esh-ui-route-tab-text' => 'Pasif sekme metin rengi',
            '--esh-ui-route-tab-bg' => 'Pasif sekme zemin',
            '--esh-ui-route-tab-border' => 'Sekme kenarlığı',
            '--esh-ui-route-tab-border-hover' => 'Sekme hover — üst kenarlık',
            '--esh-ui-route-tab-hover-bg' => 'Sekme hover zemin',
            '--esh-ui-route-tab-hover-text' => 'Sekme hover metin',
            '--esh-ui-route-tab-active-text' => 'Aktif sekme metin (genel)',
            '--esh-ui-route-tab-active-bg' => 'Aktif sekme zemin',
            '--esh-ui-route-tab-active-shadow' => 'Aktif sekme iç gölge',
            '--esh-ui-route-tab-accent-sabah' => 'Sabah — üst şerit ve dolu rozet',
            '--esh-ui-route-tab-accent-ogle' => 'Öğle — üst şerit ve dolu rozet',
            '--esh-ui-route-tab-accent-aksam' => 'Akşam — üst şerit ve dolu rozet',
            '--esh-ui-route-tab-active-sabah' => 'Sabah — aktif sekme metin',
            '--esh-ui-route-tab-active-ogle' => 'Öğle — aktif sekme metin',
            '--esh-ui-route-tab-active-aksam' => 'Akşam — aktif sekme metin',
            '--esh-ui-route-tab-badge-empty' => 'Sekme rozeti — sıfır kayıt rengi',
            '--esh-ui-route-tab-badge-font-size' => 'Sekme rozeti boyutu',
            '--esh-ui-route-tab-badge-font-size-sm' => 'Mobil — sekme rozeti',
            '--esh-ui-route-tab-icon-size' => 'Sekme ikon (güneş vb.)',
            '--esh-ui-route-tab-min-height' => 'Sekme minimum yükseklik',
            '--esh-ui-route-tab-min-height-sm' => 'Mobil — sekme yükseklik',
        ],
        'Hasta kartı' => [
            '--esh-ui-detail-tabs-bg' => 'Hasta kartı — #hastaTab sekme şeridi zemin',
            '--esh-ui-detail-tab-active-bar' => 'Aktif sekme alt çizgi rengi',
            '--esh-ui-detail-tab-link-bg' => 'Pasif sekme yarı saydam zemin',
            '--esh-ui-detail-tab-hover-bg' => 'Sekme hover zemin',
            '--esh-ui-detail-tab-hover-inset' => 'Sekme hover alt vurgu',
            '--esh-ui-detail-tab-active-bg' => 'Aktif sekme zemin',
            '--esh-ui-detail-tab-active-text' => 'Aktif sekme metin',
            '--esh-ui-detail-tab-genel-text' => 'Genel sekmesi — pasif metin',
            '--esh-ui-detail-tab-pansuman-text' => 'Pansuman sekmesi — pasif metin',
            '--esh-ui-detail-tab-sonda-text' => 'Sonda sekmesi — pasif metin',
            '--esh-ui-detail-tab-mama-text' => 'Mama sekmesi — pasif metin',
            '--esh-ui-detail-tab-bez-text' => 'Bez sekmesi — pasif metin',
            '--esh-ui-care-item-shadow' => 'Bakım sekmesi — kayıt kartı gölgesi',
            '--esh-ui-care-label-font-size' => 'Bakım kartı — üst etiket',
            '--esh-ui-care-value-font-size' => 'Bakım kartı — değer metni',
            '--esh-ui-care-icon-font-size' => 'Bakım kartı — sol ikon',
            '--esh-ui-barthel-tooltip-font-size' => 'Barthel formu — tooltip gövde',
            '--esh-ui-barthel-pts-font-size' => 'Barthel tooltip — puan sütunu',
            '--esh-ui-diagnosis-rapor-font-size' => 'Tanı satırı — «R» rapor işareti',
            '--esh-ui-coords-btn-shadow' => 'Kimlik kartı — Bul/Git düğme gölgesi',
            '--esh-ui-coords-btn-shadow-hover' => 'Bul/Git — hover gölge',
            '--esh-ui-coords-bul-bg' => 'Kimlik kartı — Bul düğme gradyan',
            '--esh-ui-coords-bul-bg-hover' => 'Bul düğme — hover gradyan',
            '--esh-ui-coords-bul-border' => 'Bul düğme kenarlık',
            '--esh-ui-coords-bul-border-hover' => 'Bul düğme hover kenarlık',
            '--esh-ui-coords-bul-color' => 'Bul düğme metin',
            '--esh-ui-coords-bul-color-hover' => 'Bul düğme hover metin',
            '--esh-ui-coords-git-bg' => 'Kimlik kartı — Git düğme gradyan (tema override edebilir)',
            '--esh-ui-coords-git-bg-hover' => 'Git düğme — hover gradyan',
            '--esh-ui-coords-git-border' => 'Git düğme kenarlık',
            '--esh-ui-coords-git-border-hover' => 'Git düğme hover kenarlık',
            '--esh-ui-coords-git-color' => 'Git düğme metin rengi',
            '--esh-ui-pansuman-day-font-size' => 'Hasta düzenle — pansuman gün kutuları',
        ],
        'Hasta mega menü' => [
            '--esh-ui-mega-menu-col-bg' => 'Birleşik liste — İzlem|Hasta sütun zemin',
            '--esh-ui-mega-menu-col-border' => 'Mega menü sütun kenarlığı',
            '--esh-ui-mega-menu-head-font-size' => 'Mega menü üst başlık',
            '--esh-ui-mega-menu-title-font-size' => 'Sütun başlığı (h6)',
            '--esh-ui-mega-menu-item-font-size' => 'Sütun içi menü öğesi',
            '--esh-ui-mega-menu-section-title-font-size' => 'Admin mega menü — bölüm başlığı',
        ],
        'Giriş sayfası' => [
            '--esh-ui-login-card-max' => 'Meridian/Aurora/WinUI — kart max genişlik',
            '--esh-ui-login-page-bg' => 'Giriş — sayfa arka planı (tema ezebilir)',
            '--esh-ui-login-font-family' => 'Giriş — yazı tipi',
            '--esh-ui-login-card-width' => 'Basit login kartı genişliği',
            '--esh-ui-login-card-radius' => 'Login kartı köşe',
            '--esh-ui-login-card-shadow' => 'Login kartı gölgesi',
            '--esh-ui-login-header-bg' => 'Login kartı — mavi üst şerit',
            '--esh-ui-login-header-color' => 'Login üst şerit — metin',
            '--esh-ui-login-header-icon-size' => 'Login üst şerit — ikon boyutu',
            '--esh-ui-login-input-radius' => 'Giriş formu — input köşe',
            '--esh-ui-login-input-padding' => 'Giriş formu — input padding',
            '--esh-ui-login-input-border' => 'Giriş formu — input kenarlık',
            '--esh-ui-login-btn-radius' => 'Giriş — Giriş yap düğme köşe',
            '--esh-ui-login-btn-padding' => 'Giriş düğme padding',
            '--esh-ui-login-split-page-bg' => 'Giriş (iki mod) — sayfa arka planı',
            '--esh-ui-login-split-card-width' => 'Giriş (iki mod) — kart genişliği',
            '--esh-ui-login-split-card-radius' => 'Giriş (iki mod) — kart köşe',
            '--esh-ui-login-split-card-shadow' => 'Giriş (iki mod) — kart gölgesi',
            '--esh-ui-login-split-card-border' => 'Giriş (iki mod) — kart kenarlığı',
            '--esh-ui-login-split-header-bg' => 'Giriş (iki mod) — üst şerit gradyan',
            '--esh-ui-login-split-brand-icon-size' => 'Giriş (iki mod) — marka ikon boyutu',
            '--esh-ui-login-split-subtitle-font-size' => 'Giriş (iki mod) — alt başlık boyutu',
            '--esh-ui-login-split-mode-border' => 'Giriş (iki mod) — mod kartı kenarlığı',
            '--esh-ui-login-split-normal-accent' => 'Giriş (iki mod) — normal giriş üst şerit',
            '--esh-ui-login-split-eimza-accent' => 'Giriş (iki mod) — e-imza mod üst şerit',
            '--esh-ui-login-split-tab-color' => 'Giriş (iki mod) — sekme pasif metin',
            '--esh-ui-login-split-tab-active-color' => 'Giriş (iki mod) — sekme aktif metin',
            '--esh-ui-login-split-tab-border' => 'Giriş (iki mod) — sekme / kart ayırıcı',
            '--esh-ui-login-split-input-border' => 'Giriş (iki mod) — input kenarlığı',
            '--esh-ui-login-split-input-group-bg' => 'Giriş (iki mod) — input grup zemin',
        ],
        'Adres yönetimi' => [
            '--esh-ui-hier-header-bg' => '#adres-hierarchy — sütun başlık çubuğu',
            '--esh-ui-hier-header-color' => 'Sütun başlık metni',
            '--esh-ui-hier-column-shadow' => 'Hiyerarşi sütun gölgesi',
            '--esh-ui-hier-item-divider' => 'Liste satırı ayırıcı',
            '--esh-ui-hier-item-active-bg' => 'Seçili adres satırı zemin',
            '--esh-ui-hier-empty-text' => 'Boş sütun mesajı rengi',
        ],
        'Formlar ve izlem' => [
            '--esh-ui-input-focus-border' => '#patientForm — odakta input kenarlık',
            '--esh-ui-input-focus-ring' => '#patientForm — odak halkası',
            '--esh-ui-profile-border-faint' => 'Profil — liste öğe kenarlığı',
            '--esh-ui-profile-aside-gradient' => 'Profil düzenle — sol kart zemin',
            '--esh-ui-profile-section-head-gradient' => 'Profil düzenle — bölüm başlık zemin',
            '--esh-ui-profile-security-head-gradient' => 'Profil — güvenlik kartı başlık',
            '--esh-ui-profile-btn-primary-shadow' => 'Profil kaydet — birincil düğme gölge',
            '--esh-ui-pansuman-label-inset-shadow' => 'Pansuman planlama — seçili gün gölgesi',
            '--esh-ui-ekip-box-border' => 'Ekip atama — kutu kenarlığı',
            '--esh-ui-danger-text' => 'Ekip atama — sil ikonu rengi',
            '--esh-ui-plan-doluluk-banner-bg' => 'Planlı izlem — doluluk üst şerit',
            '--esh-ui-pasif-banner-bg' => 'Pasif dosya / durum şeridi zemin',
            '--esh-ui-arac-choice-hover-shadow' => 'İzlem formu — araç seçici hover',
            '--esh-ui-stats-metric-fg' => 'İstatistik özeti — kart metin (beyaz)',
            '--esh-ui-stats-metric-overlay' => 'İstatistik kartı — ikon daire zemin',
            '--esh-ui-stats-metric-pill-bg' => 'İstatistik kartı — alt pill zemin',
        ],
    ];

    /**
     * Sayfa standardı editörü — üst modül filtresi (grup adları {@see GROUPS} anahtarları).
     *
     * @var array<string, list<string>>
     */
    public const MODULES = [
        'Ortak panel' => [
            'Genel sayfa',
            'Filtre satırı',
            'Uyarı mesajları (alert)',
        ],
        'Kabuk & menü' => [
            'Üst menü',
            'Kabuk ve genel tablo',
        ],
        'Listeler' => [
            'Tablolar ve hasta listeleri',
        ],
        'Dashboard & rota' => [
            'Dashboard',
            'Rota ve günün planı sekmeleri',
        ],
        'Hasta' => [
            'Hasta kartı',
            'Hasta mega menü',
        ],
        'Formlar & yönetim' => [
            'Formlar ve izlem',
            'Adres yönetimi',
        ],
        'Giriş' => [
            'Giriş sayfası',
        ],
        'Diğer' => [
            'Tema dosyasında ek',
        ],
    ];

    /** @return list<string> */
    public static function moduleOrder(): array
    {
        return array_keys(self::MODULES);
    }

    public static function moduleForGroup(string $group): string
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach (self::MODULES as $module => $groups) {
                foreach ($groups as $groupName) {
                    $map[$groupName] = $module;
                }
            }
        }

        return $map[$group] ?? 'Diğer';
    }

    /**
     * Editör pill şeridi — yalnızca jetonu olan modüller + Tümü.
     *
     * @param array<string, list<array<string, mixed>>> $tokenGroups
     * @return list<array{id:string,label:string,count:int}>
     */
    public static function editorModuleFilters(array $tokenGroups): array
    {
        $counts = [];
        foreach ($tokenGroups as $groupName => $rows) {
            $module = self::moduleForGroup((string) $groupName);
            $counts[$module] = ($counts[$module] ?? 0) + count($rows);
        }

        $total = array_sum($counts);
        if ($total === 0) {
            return [];
        }

        $out = [
            ['id' => 'all', 'label' => 'Tümü', 'count' => $total],
        ];
        foreach (self::moduleOrder() as $module) {
            if (empty($counts[$module])) {
                continue;
            }
            $out[] = [
                'id' => $module,
                'label' => $module,
                'count' => (int) $counts[$module],
            ];
        }

        return $out;
    }

    /**
     * Iframe önizleme — modül pill seçilince kaydırılacak bölüm (`preview_shell.php` id).
     *
     * @return array<string, string> modül adı veya `all` => element id
     */
    public static function modulePreviewAnchors(): array
    {
        return [
            'all' => 'esh-preview-mod-kabuk',
            'Ortak panel' => 'esh-preview-mod-ortak',
            'Kabuk & menü' => 'esh-preview-mod-kabuk',
            'Listeler' => 'esh-preview-mod-liste',
            'Dashboard & rota' => 'esh-preview-mod-dashboard',
            'Hasta' => 'esh-preview-mod-hasta',
            'Formlar & yönetim' => 'esh-preview-mod-form',
            'Giriş' => 'esh-preview-mod-giris',
            'Diğer' => 'esh-preview-mod-top',
        ];
    }

    /**
     * @return array<string, array{label: string, group: string}>
     */
    public static function tokenMeta(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [];
        foreach (self::GROUPS as $group => $tokens) {
            foreach ($tokens as $name => $label) {
                $cache[$name] = ['label' => $label, 'group' => $group];
            }
        }

        return $cache;
    }

    /**
     * @return array<string, string> jeton => kullanım etiketi (düz liste)
     */
    public static function flatLabels(): array
    {
        $out = [];
        foreach (self::tokenMeta() as $name => $meta) {
            $out[$name] = $meta['label'];
        }

        return $out;
    }

    /** @return list<string> */
    public static function groupOrder(): array
    {
        return array_keys(self::GROUPS);
    }

    /**
     * CSS bloklarından `--esh-ui-*` öntanımları (önbellekli, tema başına).
     *
     * @return array<string, string>
     */
    public static function defaultValues(?string $themeSlug = null): array
    {
        $slug = $themeSlug !== null ? trim($themeSlug) : '';
        static $cacheByTheme = [];
        if (isset($cacheByTheme[$slug])) {
            return $cacheByTheme[$slug];
        }

        $path = dirname(__DIR__, 2) . '/public/assets/esh-page-language.css';
        $out = [];
        if (is_file($path)) {
            $css = (string) file_get_contents($path);
            self::mergeEshUiVarsFromSelectorBlock($css, 'body.app-shell', $out);
            self::mergeEshUiVarsFromSelectorBlock($css, 'body.page-login', $out);
            self::mergeEshUiVarsFromSelectorBlock($css, 'body.page-login.page-login-theme-default', $out);
            if ($slug === 'default') {
                self::mergeEshUiVarsFromSelectorBlock($css, 'body.theme-default', $out);
            }
        }

        return $cacheByTheme[$slug] = $out;
    }

    /**
     * @param array<string, string> $into
     */
    private static function mergeEshUiVarsFromSelectorBlock(string $css, string $selector, array &$into): void
    {
        $pattern = '/' . preg_quote($selector, '/') . '\s*\{([^}]*)\}/s';
        if (!preg_match($pattern, $css, $block)) {
            return;
        }
        if (!preg_match_all('/(--esh-ui-[a-z0-9-]+)\s*:\s*([^;]+);/', $block[1], $rows, PREG_SET_ORDER)) {
            return;
        }
        foreach ($rows as $row) {
            $into[$row[1]] = trim($row[2]);
        }
    }
}
