<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Yönetim navbar mega menüsü — tek kaynak (gruplu bağlantılar).
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
        $active = isset($_GET['tab']) ? (string) $_GET['tab'] : 'modules';

        return in_array($active, ['modules', 'islem_ids', 'harita', 'planlama', 'kurumsal', 'nobet'], true);
    }

    private static function settingsUrl(string $tab = 'modules'): string
    {
        return $tab === 'modules'
            ? esh_url('Settings', 'index')
            : esh_url('Settings', 'index', ['tab' => $tab]);
    }

    private static function userListActive(string $currentController, string $currentAction): bool
    {
        return $currentController === 'User'
            && in_array($currentAction, ['list', 'create', 'adminEdit', 'store', 'image', 'upload', 'cropsave'], true);
    }

    private static function isKurumAdminMenu(): bool
    {
        return AuthHelper::sessionIsAdmin() && !AuthHelper::sessionIsSuperAdmin();
    }

    /**
     * @return array<int, array{title: string, icon: string, accent: string, items: list<array{label: string, href: string, icon: string, icon_class: string, title: string, active: bool}>}>
     */
    public static function menuGroups(string $currentController, string $currentAction): array
    {
        $kurumAdmin = self::isKurumAdminMenu();

        $ekipKurumItems = [];
        if ($kurumAdmin) {
            $ekipKurumItems[] = self::link(
                'Kullanıcı yönetimi',
                esh_url('User', 'list'),
                'fa-users-gear',
                'text-primary',
                self::userListActive($currentController, $currentAction)
            );
        }
        $ekipKurumItems[] = self::link('Araç yönetimi', esh_url('Arac', 'index'), 'fa-truck-medical', 'text-info', $currentController === 'Arac');

        if (AppSettings::isModuleEnabled('nobet')) {
            $ekipKurumItems[] = self::link(
                'Nöbet planı',
                esh_url('Nobet', 'index'),
                'fa-calendar-week',
                'text-primary',
                $currentController === 'Nobet' && $currentAction === 'index'
            );
        }

        if (AppSettings::isModuleEnabled('ekip')) {
            $ekipKurumItems[] = self::link(
                'Ekip planlama',
                esh_url('Ekip', 'index'),
                'fa-people-group',
                'text-primary',
                $currentController === 'Ekip'
            );
        }

        if ($kurumAdmin) {
            $ekipKurumItems[] = self::link(
                'Kurum ayarları',
                self::settingsUrl('modules'),
                'fa-building',
                'text-primary',
                self::kurumSettingsActive($currentController)
            );
            $ekipKurumItems[] = self::link(
                'Branş seçimi',
                esh_url('Brans', 'index'),
                'fa-hospital-user',
                'text-info',
                $currentController === 'Brans'
            );
            $ekipKurumItems[] = self::link(
                'Hastalık seçimi',
                esh_url('Hastalik', 'index'),
                'fa-virus',
                'text-danger',
                $currentController === 'Hastalik'
            );
            $ekipKurumItems[] = self::link(
                'EK-3 amaç seçimi',
                esh_url('Istek', 'index'),
                'fa-clipboard-list',
                'text-primary',
                $currentController === 'Istek'
            );
            $ekipKurumItems[] = self::link(
                'İşlem seçimi',
                esh_url('Islem', 'index'),
                'fa-list-check',
                'text-secondary',
                $currentController === 'Islem'
            );
        }

        $sahaItems = [
            self::link('Mahalle planlama', esh_url('Planning', 'index'), 'fa-map-location-dot', 'text-success', $currentController === 'Planning'),
            self::link('Pansuman düzenleme', esh_url('Pansuman', 'index'), 'fa-bandage', 'text-success', $currentController === 'Pansuman'),
        ];

        if (AppSettings::isModuleEnabled('adrestanim')) {
            $sahaItems[] = self::link(
                'Adres tanımları',
                esh_url('Adrestanim', 'index'),
                'fa-location-dot',
                'text-success',
                $currentController === 'Adrestanim'
            );
        }

        if (AppSettings::isModuleEnabled('archive')) {
            $sahaItems[] = self::link(
                'Hasta dosya sistemi',
                esh_url('Archive', 'index'),
                'fa-box-archive',
                'text-success',
                $currentController === 'Archive'
            );
        }

        if (AppSettings::isModuleEnabled('sms_bildirim') && \App\Services\Sms\SmsService::moduleReady()) {
            $sahaItems[] = self::link(
                'SMS bildirimleri',
                esh_url('Sms', 'index'),
                'fa-comment-sms',
                'text-success',
                $currentController === 'Sms',
                'Hasta, yakın ve aile hekimine bilgilendirme SMS'
            );
        }

        if (AppSettings::isModuleEnabled('stok') && \App\Services\Stok\StokService::moduleReady()) {
            $sahaItems[] = self::link(
                'Stok takibi',
                esh_url('Stok', 'index'),
                'fa-boxes-stacked',
                'text-success',
                $currentController === 'Stok',
                'Malzeme stoku, giriş/çıkış ve dağıtım'
            );
        }

        $groups = [
            [
                'title' => 'Ekip ve kurum',
                'icon' => 'fa-users-gear',
                'accent' => 'primary',
                'items' => $ekipKurumItems,
            ],
            [
                'title' => 'Saha operasyonları',
                'icon' => 'fa-route',
                'accent' => 'success',
                'items' => $sahaItems,
            ],
            [
                'title' => 'Raporlar ve analiz',
                'icon' => 'fa-chart-pie',
                'accent' => 'info',
                'items' => [
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
                ],
            ],
            [
                'title' => 'Kritik hasta akışları',
                'icon' => 'fa-triangle-exclamation',
                'accent' => 'danger',
                'items' => [
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
                ],
            ],
        ];

        return array_values(array_filter($groups, static fn (array $g): bool => $g['items'] !== []));
    }

    /**
     * @return array{label: string, href: string, icon: string, icon_class: string, title: string, active: bool}
     */
    private static function link(
        string $label,
        string $href,
        string $icon,
        string $iconClass,
        bool $active,
        string $title = ''
    ): array {
        return [
            'label' => $label,
            'href' => $href,
            'icon' => $icon,
            'icon_class' => $iconClass,
            'title' => $title,
            'active' => $active,
        ];
    }

    public static function renderMegaMenu(string $currentController, string $currentAction): void
    {
        $groups = self::menuGroups($currentController, $currentAction);
        $colClass = 'col-12 col-lg-3';
        ?>
        <div class="dropdown-menu esh-admin-mega-menu shadow-lg border-0 w-100 mt-2 p-4 rounded-4 animate-fade-in" aria-labelledby="adminDropdown">
            <div class="row g-3 esh-admin-mega-menu__grid">
                <?php foreach ($groups as $group): ?>
                <div class="<?= htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8') ?> esh-admin-mega-menu__section">
                    <div class="esh-admin-mega-menu__section-title esh-admin-mega-menu__section-title--<?= htmlspecialchars((string) ($group['accent'] ?? 'secondary'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid <?= htmlspecialchars((string) ($group['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8') ?> me-2 text-<?= htmlspecialchars((string) ($group['accent'] ?? 'secondary'), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?= htmlspecialchars((string) ($group['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="esh-admin-mega-menu__col border rounded-4 p-3 h-100">
                        <div class="esh-admin-mega-menu__links">
                            <?php foreach ($group['items'] as $item): ?>
                            <?php $titleAttr = ($item['title'] ?? '') !== '' ? ' title="' . htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
                            <a class="dropdown-item rounded-3 py-2<?= self::itemActive(!empty($item['active'])) ?>" href="<?= htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $titleAttr ?>><i class="fa-solid <?= htmlspecialchars((string) $item['icon'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) $item['icon_class'], ENT_QUOTES, 'UTF-8') ?> me-2 opacity-75"></i><?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
