<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Helpers\AuthHelper;
/**
 * Yönetim navbar mega menüsü — tek kaynak (gruplu bağlantılar).
 * Kurum yöneticisi ve bölge yöneticisi öğeleri role göre filtrelenir.
 */
class AdminNavHelper
{
    private static function itemActive(bool $on): string
    {
        return $on ? ' active' : '';
    }

    private static function patientUnifiedMatch(string $status, string $currentController, string $currentAction): bool
    {
        if ($currentController !== 'Patient' || $currentAction !== 'unified') {
            return false;
        }
        $q = isset($_GET['status']) ? (string) $_GET['status'] : '';

        return $q === $status;
    }

    private static function kurumSettingsActive(string $currentController): bool
    {
        if ($currentController !== 'Settings') {
            return false;
        }
        $raw = isset($_GET['tab']) ? (string) $_GET['tab'] : 'modules';
        $active = \App\Helpers\SettingsNavCatalog::resolveTab($raw);

        return in_array($active, \App\Helpers\SettingsNavCatalog::tabsForRole(), true);
    }

    private static function settingsUrl(string $tab = 'modules'): string
    {
        return $tab === 'modules'
            ? esh_url('Settings', 'index')
            : esh_url('Settings', 'index', ['tab' => $tab]);
    }

    private static function userAdminListActive(string $currentController, string $currentAction): bool
    {
        return $currentController === 'User'
            && in_array($currentAction, ['list', 'create', 'adminEdit', 'store', 'image', 'upload', 'cropsave'], true)
            && isset($_GET['role'])
            && (string) $_GET['role'] === 'admin';
    }

    private static function userListActive(string $currentController, string $currentAction): bool
    {
        if ($currentController !== 'User'
            || !in_array($currentAction, ['list', 'create', 'adminEdit', 'store', 'image', 'upload', 'cropsave'], true)) {
            return false;
        }
        if (isset($_GET['role']) && (string) $_GET['role'] === 'admin') {
            return false;
        }

        return true;
    }

    private static function isKurumAdminMenu(): bool
    {
        return AuthHelper::sessionIsAdmin() && !AuthHelper::sessionIsSuperAdmin();
    }

    private static function isSuperAdminMenu(): bool
    {
        return AuthHelper::sessionIsSuperAdmin();
    }

    private static function isPlatformOwnerMenu(): bool
    {
        return AuthHelper::sessionIsPlatformOwner();
    }

    private static function menuRoleLabel(): string
    {
        if (self::isPlatformOwnerMenu()) {
            return AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER);
        }
        if (self::isSuperAdminMenu()) {
            return AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN);
        }

        return AuthHelper::adminLevelLabel(AuthHelper::ROLE_ADMIN);
    }

    /**
     * @return array<int, array{title: string, icon: string, accent: string, items: list<array{label: string, href: string, icon: string, icon_class: string, title: string, active: bool, danger?: bool, full_width?: bool}>}>
     */
    public static function menuGroups(string $currentController, string $currentAction): array
    {
        $kurumAdmin = self::isKurumAdminMenu();
        $superAdmin = self::isSuperAdminMenu();
        $platformOwner = self::isPlatformOwnerMenu();

        // --- Grup 1: Kurum ve platform ---
        $platformItems = [];
        if ($platformOwner) {
            $platformItems = array_values(array_filter([
                self::link('Federasyon özeti', esh_url('Federation', 'index'), 'fa-diagram-project', 'text-primary', $currentController === 'Federation'),
                self::link('Federasyon bölgeleri', esh_url('FederationRegion', 'index'), 'fa-map', 'text-primary', $currentController === 'FederationRegion'),
                self::link('Federasyon köprüsü', esh_url('FederationBridge', 'index'), 'fa-file-export', 'text-primary', $currentController === 'FederationBridge'),
                \App\Services\MesajService::canUseMessaging((AuthHelper::sessionUserId() ?? ''))
                    ? self::link(
                        'Sistem duyurusu',
                        esh_url('Mesaj', 'broadcast'),
                        'fa-bullhorn',
                        'text-warning',
                        $currentController === 'Mesaj' && $currentAction === 'broadcast'
                    )
                    : null,
                self::link('Personel ünvanları', esh_url('Unvan', 'index'), 'fa-id-badge', 'text-primary', $currentController === 'Unvan'),
            ]));
        }
        if ($superAdmin) {
            $platformItems = array_merge($platformItems, array_values(array_filter([
                self::link('Kurum yönetimi', esh_url('Kurum', 'index'), 'fa-building', 'text-dark', $currentController === 'Kurum'),
                self::link('Kurum adres ataması', esh_url('KurumAdres', 'index'), 'fa-map-location-dot', 'text-dark', $currentController === 'KurumAdres'),
                self::link('Rol ve izin yönetimi', esh_url('Role', 'index'), 'fa-user-shield', 'text-primary', $currentController === 'Role'),
            ])));
        }
        if (AuthHelper::sessionIsAdmin()) {
            $platformItems[] = self::link(
                'Uygulama ayarları',
                self::settingsUrl('modules'),
                'fa-sliders',
                'text-primary',
                self::kurumSettingsActive($currentController),
                $superAdmin
                    ? ($platformOwner
                        ? 'Modül, harita, planlama, güvenlik ve platform ayarları'
                        : 'Modül, harita, planlama ve platform ayarları')
                    : 'Modül, harita, planlama ve kurumsal ayarlar'
            );
        }

        // --- Grup 2: Ekip ve saha ---
        $ekipSahaItems = [];
        if ($kurumAdmin) {
            $ekipSahaItems[] = self::link(
                'Kullanıcı yönetimi',
                esh_url('User', 'list'),
                'fa-users-gear',
                'text-primary',
                self::userListActive($currentController, $currentAction)
            );
        }
        if ($superAdmin) {
            $ekipSahaItems[] = self::link(
                'Kullanıcı yönetimi',
                esh_url('User', 'list'),
                'fa-users-gear',
                'text-primary',
                self::userListActive($currentController, $currentAction)
            );
            $ekipSahaItems[] = self::link(
                'Yönetici hesapları',
                esh_url('User', 'list', ['role' => 'admin']),
                'fa-user-shield',
                'text-dark',
                self::userAdminListActive($currentController, $currentAction)
            );
        }
        $ekipSahaItems[] = self::link('Araç yönetimi', esh_url('Arac', 'index'), 'fa-truck-medical', 'text-info', $currentController === 'Arac');
        if (AppSettings::isModuleEnabled('nobet')) {
            $ekipSahaItems[] = self::link(
                'Nöbet planı',
                esh_url('Nobet', 'index'),
                'fa-calendar-week',
                'text-primary',
                $currentController === 'Nobet' && $currentAction === 'index'
            );
        }
        if (AppSettings::isModuleEnabled('ekip')) {
            $ekipSahaItems[] = self::link(
                'Ekip planlama',
                esh_url('Ekip', 'index'),
                'fa-people-group',
                'text-primary',
                $currentController === 'Ekip'
            );
        }
        $ekipSahaItems[] = self::link('Mahalle planlama', esh_url('Planning', 'index'), 'fa-map-location-dot', 'text-success', $currentController === 'Planning');
        $ekipSahaItems[] = self::link('Pansuman düzenleme', esh_url('Pansuman', 'index'), 'fa-bandage', 'text-success', $currentController === 'Pansuman');
        if (AppSettings::isModuleEnabled('adrestanim')) {
            $ekipSahaItems[] = self::link(
                'Adres tanımları',
                esh_url('Adrestanim', 'index'),
                'fa-location-dot',
                'text-success',
                $currentController === 'Adrestanim'
            );
        }
        if (AppSettings::isModuleEnabled('manuel_koordinat')) {
            $ekipSahaItems[] = self::link(
                'Manuel koordinat düzeltme',
                esh_url('ManuelKoordinat', 'index'),
                'fa-map-pin',
                'text-warning',
                $currentController === 'ManuelKoordinat',
                'Haritadan kapı koordinatı işaretleme'
            );
        }
        if (AppSettings::isModuleEnabled('archive')) {
            $ekipSahaItems[] = self::link(
                'Hasta dosya sistemi',
                esh_url('Archive', 'index'),
                'fa-box-archive',
                'text-success',
                $currentController === 'Archive'
            );
        }
        if (AppSettings::isModuleEnabled('sms_bildirim') && \App\Services\Sms\SmsService::moduleReady()) {
            $ekipSahaItems[] = self::link(
                'SMS bildirimleri',
                esh_url('Sms', 'index'),
                'fa-comment-sms',
                'text-success',
                $currentController === 'Sms',
                'Hasta, yakın ve aile hekimine bilgilendirme SMS'
            );
        }
        if (AppSettings::isModuleEnabled('stok') && \App\Services\Stok\StokService::moduleReady()) {
            $ekipSahaItems[] = self::link(
                'Stok takibi',
                esh_url('Stok', 'index'),
                'fa-boxes-stacked',
                'text-success',
                $currentController === 'Stok',
                'Malzeme stoku, giriş/çıkış ve dağıtım'
            );
        }

        // --- Grup 3: Katalog ---
        $katalogItems = [];
        if ($platformOwner) {
            $katalogItems = [
                self::link('Güvence yönetimi', esh_url('Guvence', 'index'), 'fa-shield-heart', 'text-dark', $currentController === 'Guvence'),
                self::link('Hastalık yönetimi', esh_url('Hastalik', 'index'), 'fa-virus', 'text-danger', $currentController === 'Hastalik'),
                self::link('İşlem yönetimi', esh_url('Islem', 'index'), 'fa-list-check', 'text-secondary', $currentController === 'Islem'),
                self::link('Branş ve kota yönetimi', esh_url('Brans', 'index'), 'fa-building-user', 'text-info', $currentController === 'Brans'),
                self::link('İlaç Listesi Sistemi', esh_url('IlacListesi', 'index'), 'fa-pills', 'text-dark', $currentController === 'IlacListesi', 'TİTCK Modül 43 Excel → ilac-listesi.json'),
            ];
        }
        if ($superAdmin) {
            $katalogItems = array_merge($katalogItems, [
                self::link('EK-3 başvuru amaçları yönetimi', esh_url('Istek', 'index'), 'fa-clipboard-list', 'text-primary', $currentController === 'Istek'),
            ]);
            if (!$platformOwner) {
                $katalogItems = array_merge($katalogItems, [
                    self::link('Branş seçimi', esh_url('Brans', 'index'), 'fa-hospital-user', 'text-info', $currentController === 'Brans'),
                    self::link('Hastalık seçimi', esh_url('Hastalik', 'index'), 'fa-virus', 'text-danger', $currentController === 'Hastalik'),
                    self::link('İşlem seçimi', esh_url('Islem', 'index'), 'fa-list-check', 'text-secondary', $currentController === 'Islem'),
                ]);
            }
        } elseif ($kurumAdmin) {
            $katalogItems = [
                self::link('Branş seçimi', esh_url('Brans', 'index'), 'fa-hospital-user', 'text-info', $currentController === 'Brans'),
                self::link('Hastalık seçimi', esh_url('Hastalik', 'index'), 'fa-virus', 'text-danger', $currentController === 'Hastalik'),
                self::link('EK-3 amaç seçimi', esh_url('Istek', 'index'), 'fa-clipboard-list', 'text-primary', $currentController === 'Istek'),
                self::link('İşlem seçimi', esh_url('Islem', 'index'), 'fa-list-check', 'text-secondary', $currentController === 'Islem'),
            ];
        }

        // --- Grup 4: Raporlar ve entegrasyon ---
        $raporItems = [
            self::link(
                'İstatistik merkezi',
                esh_url('Stats', 'index'),
                'fa-chart-pie',
                'text-info',
                $currentController === 'Stats' && $currentAction === 'index'
            ),
            self::link(
                'Adrese göre hastalar',
                esh_url('Stats', 'adresPatientFilter'),
                'fa-map-pin',
                'text-info',
                $currentController === 'Stats' && $currentAction === 'adresPatientFilter'
            ),
        ];
        if ($superAdmin) {
            $raporItems[] = self::link(
                'İşlem günlüğü (denetim)',
                esh_url('AuditLog', 'index'),
                'fa-clipboard-list',
                'text-warning',
                $currentController === 'AuditLog'
            );
        }
        if ($platformOwner) {
            $raporItems = array_merge($raporItems, [
                self::link(
                    'ESYS alan eşlemesi',
                    esh_url('EsysCompliance', 'index'),
                    'fa-link',
                    'text-secondary',
                    $currentController === 'EsysCompliance'
                ),
                self::link(
                    'ESYS / AHBS köprüsü',
                    esh_url('EsysBridge', 'index'),
                    'fa-bridge',
                    'text-secondary',
                    $currentController === 'EsysBridge'
                ),
                self::link(
                    'USBS alan eşlemesi',
                    esh_url('UsbsCompliance', 'index'),
                    'fa-heart-pulse',
                    'text-info',
                    $currentController === 'UsbsCompliance'
                ),
                self::link(
                    'USBS / e-Nabız köprüsü',
                    esh_url('UsbsBridge', 'index'),
                    'fa-file-export',
                    'text-info',
                    $currentController === 'UsbsBridge'
                ),
            ]);
        }
        if (AppSettings::isModuleEnabled('patient_portal')) {
            $raporItems[] = self::link(
                'Portal randevu talepleri',
                esh_url('PortalAppointment', 'index'),
                'fa-calendar-check',
                'text-primary',
                $currentController === 'PortalAppointment'
            );
        }
        if ($platformOwner) {
            $raporItems[] = self::link(
                'REST API tokenları',
                esh_url('ApiToken', 'index'),
                'fa-key',
                'text-info',
                $currentController === 'ApiToken'
            );
        }

        // --- Grup 5: Altyapı ve veri (süper yönetici) ---
        $altyapiItems = [];
        if ($superAdmin) {
            $altyapiItems = array_values(array_filter([
                self::link('Tema görünümü yönetimi', esh_url('Theme', 'index'), 'fa-palette', 'text-dark', $currentController === 'Theme'),
                $platformOwner
                    ? self::link('CDN sürüm kontrolü', esh_url('CdnCheck', 'index'), 'fa-cloud-arrow-down', 'text-dark', $currentController === 'CdnCheck', 'Bootstrap, jQuery, Toastr vb. sabit sürümler — npm/cdnjs')
                    : null,
                self::link('Denizli adres senkronu', esh_url('AdresFetch', 'index'), 'fa-cloud-arrow-down', 'text-info', $currentController === 'AdresFetch' && $currentAction === 'index'),
                self::link('Adres ağacı', esh_url('AdresFetch', 'tree'), 'fa-sitemap', 'text-secondary', $currentController === 'AdresFetch' && $currentAction === 'tree'),
                self::link('Eksik ilçe taraması', esh_url('AdresFetch', 'tarama'), 'fa-magnifying-glass', 'text-warning', $currentController === 'AdresFetch' && $currentAction === 'tarama'),
                AppSettings::isModuleEnabled('adres_koordinat')
                    ? self::link('Adres koordinat bulma', esh_url('AdresKoordinat', 'index'), 'fa-location-crosshairs', 'text-primary', $currentController === 'AdresKoordinat')
                    : null,
                AppSettings::isModuleEnabled('harita')
                    ? self::link('Hasta haritası', esh_url('Harita', 'index'), 'fa-map-marked-alt', 'text-danger', $currentController === 'Harita')
                    : null,
                AppSettings::isModuleEnabled('ilac_rehber')
                    ? self::link('İlaç rehberi (veri özeti)', esh_url('IlacRehber', 'index'), 'fa-book-medical', 'text-dark', $currentController === 'IlacRehber' && $currentAction === 'index')
                    : null,
                AppSettings::isModuleEnabled('ilac_rehber')
                    ? self::link('İlaç rehberi veri aktarımı', esh_url('IlacRehber', 'migration'), 'fa-pills', 'text-success', $currentController === 'IlacRehber' && $currentAction === 'migration')
                    : null,
            ]));
        }

        // --- Grup 6: Kritik ve bakım ---
        $kritikItems = [
            self::link(
                'Muhtemel ölenler',
                esh_url('Patient', 'unified', ['status' => 'probable']),
                'fa-user-xmark',
                'text-danger',
                self::patientUnifiedMatch('probable', $currentController, $currentAction)
                    || self::patientUnifiedMatch('died', $currentController, $currentAction)
            ),
            self::link(
                'Araf (beklerken ölenler)',
                esh_url('Patient', 'unified', ['status' => 'araf']),
                'fa-hourglass-half',
                'text-warning',
                self::patientUnifiedMatch('araf', $currentController, $currentAction)
            ),
            self::link(
                'Silinen hastalar',
                esh_url('Patient', 'unified', ['status' => 'deleted']),
                'fa-trash-can',
                'text-secondary',
                self::patientUnifiedMatch('deleted', $currentController, $currentAction)
            ),
            self::link(
                'Vefat taraması (aktif)',
                esh_url('Patient', 'scan'),
                'fa-magnifying-glass',
                'text-success',
                $currentController === 'Patient' && $currentAction === 'scan'
            ),
            self::link(
                'Vefat taraması (bekleyen)',
                esh_url('Patient', 'scanWaiting'),
                'fa-magnifying-glass',
                'text-info',
                $currentController === 'Patient' && $currentAction === 'scanWaiting'
            ),
            self::link(
                'Pasif — bekleyen planlı izlemler',
                esh_url('PlannedVisit', 'passivePendingPlans'),
                'fa-user-clock',
                'text-danger',
                $currentController === 'PlannedVisit' && $currentAction === 'passivePendingPlans'
            ),
        ];
        if ($platformOwner) {
            $kritikItems[] = self::link(
                'Veritabanı bakım / yedek sistemi',
                esh_url('DbMaintenance', 'index'),
                'fa-database',
                'text-danger',
                $currentController === 'DbMaintenance',
                '',
                true,
                true
            );
        }

        $groups = [
            [
                'title' => 'Kurum ve platform',
                'icon' => 'fa-building-columns',
                'accent' => 'dark',
                'items' => $platformItems,
            ],
            [
                'title' => 'Ekip ve saha',
                'icon' => 'fa-route',
                'accent' => 'success',
                'items' => $ekipSahaItems,
            ],
            [
                'title' => 'Katalog',
                'icon' => 'fa-tags',
                'accent' => 'info',
                'items' => $katalogItems,
            ],
            [
                'title' => 'Raporlar ve entegrasyon',
                'icon' => 'fa-chart-pie',
                'accent' => 'info',
                'items' => $raporItems,
            ],
            [
                'title' => 'Altyapı ve veri',
                'icon' => 'fa-server',
                'accent' => 'primary',
                'items' => $altyapiItems,
            ],
            [
                'title' => 'Kritik ve bakım',
                'icon' => 'fa-triangle-exclamation',
                'accent' => 'danger',
                'items' => $kritikItems,
            ],
        ];

        return array_values(array_filter($groups, static fn (array $g): bool => $g['items'] !== []));
    }

    /**
     * @return array{label: string, href: string, icon: string, icon_class: string, title: string, active: bool, danger?: bool, full_width?: bool}
     */
    private static function link(
        string $label,
        string $href,
        string $icon,
        string $iconClass,
        bool $active,
        string $title = '',
        bool $danger = false,
        bool $fullWidth = false
    ): array {
        $item = [
            'label' => $label,
            'href' => $href,
            'icon' => $icon,
            'icon_class' => $iconClass,
            'title' => $title,
            'active' => $active,
        ];
        if ($danger) {
            $item['danger'] = true;
        }
        if ($fullWidth) {
            $item['full_width'] = true;
        }

        return $item;
    }

    /**
     * @deprecated Mega menü yerine renderOffcanvas kullanılır.
     */
    public static function renderMegaMenu(string $currentController, string $currentAction): void
    {
        self::renderOffcanvas($currentController, $currentAction);
    }

    public static function renderOffcanvas(string $currentController, string $currentAction): void
    {
        $groups = self::menuGroups($currentController, $currentAction);
        if ($groups === []) {
            return;
        }
        $roleLabel = self::menuRoleLabel();
        $totalLinks = 0;
        foreach ($groups as $group) {
            $totalLinks += count($group['items']);
        }
        ?>
        <div class="offcanvas offcanvas-start esh-admin-offcanvas" id="eshAdminOffcanvas" tabindex="-1" aria-labelledby="eshAdminOffcanvasLabel">
            <div class="offcanvas-header esh-admin-offcanvas__header border-0">
                <div class="esh-admin-offcanvas__header-main">
                    <div class="esh-admin-offcanvas__header-icon" aria-hidden="true">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="min-w-0">
                        <h5 class="offcanvas-title fw-bold mb-1" id="eshAdminOffcanvasLabel">Yönetim</h5>
                        <p class="esh-admin-offcanvas__subtitle mb-0">
                            <span class="badge rounded-pill esh-admin-offcanvas__role-badge"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="esh-admin-offcanvas__meta"><?= (int) count($groups) ?> grup · <?= (int) $totalLinks ?> bağlantı</span>
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close esh-admin-offcanvas__close" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
            </div>
            <div class="offcanvas-body p-0">
                <div class="accordion accordion-flush esh-admin-offcanvas__accordion" id="eshAdminNavAccordion">
                    <?php foreach ($groups as $groupIndex => $group): ?>
                    <?php
                    $groupHasActive = false;
                    foreach ($group['items'] as $groupItem) {
                        if (!empty($groupItem['active'])) {
                            $groupHasActive = true;
                            break;
                        }
                    }
                    $collapseId = 'eshAdminNavCollapse' . $groupIndex;
                    $headingId = 'eshAdminNavHeading' . $groupIndex;
                    $accent = (string) ($group['accent'] ?? 'secondary');
                    $itemCount = count($group['items']);
                    ?>
                    <div class="accordion-item esh-admin-offcanvas__group border-0<?= $groupHasActive ? ' esh-admin-offcanvas__group--active' : '' ?>">
                        <h2 class="accordion-header" id="<?= htmlspecialchars($headingId, ENT_QUOTES, 'UTF-8') ?>">
                            <button class="accordion-button esh-admin-offcanvas__group-btn esh-admin-offcanvas__group-btn--<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?><?= $groupHasActive ? '' : ' collapsed' ?>"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>"
                                    aria-expanded="<?= $groupHasActive ? 'true' : 'false' ?>"
                                    aria-controls="<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="esh-admin-offcanvas__group-icon esh-admin-offcanvas__group-icon--<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                                    <i class="fa-solid <?= htmlspecialchars((string) ($group['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8') ?>"></i>
                                </span>
                                <span class="esh-admin-offcanvas__group-text flex-grow-1 text-start">
                                    <span class="esh-admin-offcanvas__group-title"><?= htmlspecialchars((string) ($group['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                                <span class="badge rounded-pill esh-admin-offcanvas__count-badge"><?= (int) $itemCount ?></span>
                            </button>
                        </h2>
                        <div id="<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>"
                             class="accordion-collapse collapse<?= $groupHasActive ? ' show' : '' ?>"
                             aria-labelledby="<?= htmlspecialchars($headingId, ENT_QUOTES, 'UTF-8') ?>"
                             data-bs-parent="#eshAdminNavAccordion">
                            <div class="accordion-body esh-admin-offcanvas__group-body">
                                <nav class="nav flex-column esh-admin-offcanvas__links" aria-label="<?= htmlspecialchars((string) ($group['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?php foreach ($group['items'] as $item): ?>
                                    <?php
                                    $linkClass = 'nav-link esh-admin-offcanvas__link';
                                    if (!empty($item['active'])) {
                                        $linkClass .= ' active';
                                    }
                                    if (!empty($item['danger'])) {
                                        $linkClass .= ' esh-admin-offcanvas__link--danger';
                                    }
                                    $titleAttr = ($item['title'] ?? '') !== '' ? ' title="' . htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') . '"' : '';
                                    $iconClass = (string) ($item['icon_class'] ?? 'text-secondary');
                                    if (preg_match('/text-([a-z]+)/', $iconClass, $iconAccent)) {
                                        $iconAccentName = $iconAccent[1];
                                    } else {
                                        $iconAccentName = 'secondary';
                                    }
                                    ?>
                                    <a class="<?= htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $titleAttr ?>>
                                        <span class="esh-admin-offcanvas__link-icon esh-admin-offcanvas__link-icon--<?= htmlspecialchars($iconAccentName, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                                            <i class="fa-solid <?= htmlspecialchars((string) $item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                        </span>
                                        <span class="esh-admin-offcanvas__link-label"><?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </a>
                                    <?php endforeach; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
