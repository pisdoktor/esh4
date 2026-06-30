<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Süper yönetici navbar mega menüsü — tek kaynak (gruplu bağlantılar).
 */
class SuperadminNavHelper
{
    private static function itemActive(bool $on): string
    {
        return $on ? ' active' : '';
    }

    private static function settingsTabActive(string $tab, string $currentController): bool
    {
        if ($currentController !== 'Settings') {
            return false;
        }
        $active = isset($_GET['tab']) ? (string) $_GET['tab'] : 'modules';

        return $active === $tab;
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

    /**
     * @return array<int, array{title: string, icon: string, accent: string, items: list<array{label: string, href: string, icon: string, icon_class: string, title: string, active: bool, danger?: bool, full_width?: bool}>}>
     */
    public static function menuGroups(string $currentController, string $currentAction): array
    {
        $groups = [
            [
                'title' => 'Kurum ve platform',
                'icon' => 'fa-building-columns',
                'accent' => 'dark',
                'items' => array_values(array_filter([
                    self::link('Kurum yönetimi', esh_url('Kurum', 'index'), 'fa-building', 'text-dark', $currentController === 'Kurum'),
                    self::link('Kurum adres ataması', esh_url('KurumAdres', 'index'), 'fa-map-location-dot', 'text-dark', $currentController === 'KurumAdres'),
                    self::link('Güvence yönetimi', esh_url('Guvence', 'index'), 'fa-shield-heart', 'text-dark', $currentController === 'Guvence'),
                    self::link('Uygulama ayarları', self::settingsUrl('modules'), 'fa-sliders', 'text-dark', self::settingsTabActive('modules', $currentController)),
                    self::link('Rol ve izin yönetimi', esh_url('Role', 'index'), 'fa-user-shield', 'text-primary', $currentController === 'Role'),
                    self::link('Personel ünvanları', esh_url('Unvan', 'index'), 'fa-id-badge', 'text-primary', $currentController === 'Unvan'),
                    \App\Services\MesajService::canUseMessaging((int) ($_SESSION['user_id'] ?? 0))
                        ? self::link(
                            'Sistem duyurusu',
                            esh_url('Mesaj', 'broadcast'),
                            'fa-bullhorn',
                            'text-warning',
                            $currentController === 'Mesaj' && $currentAction === 'broadcast'
                        )
                        : null,
                ])),
            ],
            [
                'title' => 'Katalog tanımları',
                'icon' => 'fa-tags',
                'accent' => 'info',
                'items' => [
                    self::link('Hastalık yönetimi', esh_url('Hastalik', 'index'), 'fa-virus', 'text-danger', $currentController === 'Hastalik'),
                    self::link('İşlem yönetimi', esh_url('Islem', 'index'), 'fa-list-check', 'text-secondary', $currentController === 'Islem'),
                    self::link('Branş ve kota yönetimi', esh_url('Brans', 'index'), 'fa-building-user', 'text-info', $currentController === 'Brans'),
                    self::link('EK-3 başvuru amaçları yönetimi', esh_url('Istek', 'index'), 'fa-clipboard-list', 'text-primary', $currentController === 'Istek'),
                    self::link('İlaç Listesi Sistemi', esh_url('IlacListesi', 'index'), 'fa-pills', 'text-dark', $currentController === 'IlacListesi', 'TİTCK Modül 43 Excel → ilac-listesi.json'),
                ],
            ],
            [
                'title' => 'Altyapı ve veri',
                'icon' => 'fa-server',
                'accent' => 'primary',
                'items' => array_values(array_filter([
                    self::link('Tema görünümü yönetimi', esh_url('Theme', 'index'), 'fa-palette', 'text-dark', $currentController === 'Theme'),
                    self::link('CDN sürüm kontrolü', esh_url('CdnCheck', 'index'), 'fa-cloud-arrow-down', 'text-dark', $currentController === 'CdnCheck', 'Bootstrap, jQuery, Toastr vb. sabit sürümler — npm/cdnjs'),
                    self::link('Denizli adres senkronu', esh_url('AdresFetch', 'index'), 'fa-cloud-arrow-down', 'text-info', $currentController === 'AdresFetch' && $currentAction === 'index'),
                    self::link('Adres ağacı', esh_url('AdresFetch', 'tree'), 'fa-sitemap', 'text-secondary', $currentController === 'AdresFetch' && $currentAction === 'tree'),
                    self::link('Eksik ilçe taraması', esh_url('AdresFetch', 'tarama'), 'fa-magnifying-glass', 'text-warning', $currentController === 'AdresFetch' && $currentAction === 'tarama'),
                    AppSettings::isModuleEnabled('adres_koordinat')
                        ? self::link('Adres koordinat bulma', esh_url('AdresKoordinat', 'index'), 'fa-location-crosshairs', 'text-primary', $currentController === 'AdresKoordinat')
                        : null,
                    AppSettings::isModuleEnabled('harita')
                        ? self::link('Hasta haritası (TomTom)', esh_url('Harita', 'index'), 'fa-map-marked-alt', 'text-danger', $currentController === 'Harita')
                        : null,
                    AppSettings::isModuleEnabled('ilac_rehber')
                        ? self::link('İlaç rehberi (veri özeti)', esh_url('IlacRehber', 'index'), 'fa-book-medical', 'text-dark', $currentController === 'IlacRehber' && $currentAction === 'index')
                        : null,
                    AppSettings::isModuleEnabled('ilac_rehber')
                        ? self::link('İlaç rehberi veri aktarımı', esh_url('IlacRehber', 'migration'), 'fa-pills', 'text-success', $currentController === 'IlacRehber' && $currentAction === 'migration')
                        : null,
                ])),
            ],
            [
                'title' => 'Hesaplar ve kritik',
                'icon' => 'fa-user-shield',
                'accent' => 'danger',
                'items' => [
                    self::link('Kullanıcı yönetimi', esh_url('User', 'list'), 'fa-users-gear', 'text-primary', self::userListActive($currentController, $currentAction)),
                    self::link('Yönetici hesapları', esh_url('User', 'list', ['role' => 'admin']), 'fa-user-shield', 'text-dark', self::userAdminListActive($currentController, $currentAction)),
                    self::link('Veritabanı bakım / yedek sistemi', esh_url('DbMaintenance', 'index'), 'fa-database', 'text-danger', $currentController === 'DbMaintenance', '', true, true),
                ],
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

    public static function renderMegaMenu(string $currentController, string $currentAction): void
    {
        $groups = self::menuGroups($currentController, $currentAction);
        $colClass = 'col-12 col-lg-3';
        ?>
        <div class="dropdown-menu esh-admin-mega-menu esh-superadmin-mega-menu shadow-lg border-0 w-100 mt-2 p-4 rounded-4 animate-fade-in" aria-labelledby="superadminDropdown">
            <div class="row g-3 esh-admin-mega-menu__grid">
                <?php foreach ($groups as $group): ?>
                <div class="<?= htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8') ?> esh-admin-mega-menu__section">
                    <div class="esh-admin-mega-menu__section-title esh-admin-mega-menu__section-title--<?= htmlspecialchars((string) ($group['accent'] ?? 'secondary'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid <?= htmlspecialchars((string) ($group['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8') ?> me-2 text-<?= htmlspecialchars((string) ($group['accent'] ?? 'secondary'), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?= htmlspecialchars((string) ($group['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="esh-admin-mega-menu__col border rounded-4 p-3 h-100 esh-superadmin-mega-menu__col">
                        <div class="esh-admin-mega-menu__links">
                            <?php foreach ($group['items'] as $item): ?>
                            <?php
                            $extraClass = '';
                            if (!empty($item['danger'])) {
                                $extraClass .= ' text-danger fw-semibold';
                            }
                            if (!empty($item['full_width'])) {
                                $extraClass .= ' esh-superadmin-mega-menu__full';
                            }
                            $titleAttr = ($item['title'] ?? '') !== '' ? ' title="' . htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') . '"' : '';
                            ?>
                            <a class="dropdown-item rounded-3 py-2<?= self::itemActive(!empty($item['active'])) ?><?= $extraClass ?>" href="<?= htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $titleAttr ?>><i class="fa-solid <?= htmlspecialchars((string) $item['icon'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) $item['icon_class'], ENT_QUOTES, 'UTF-8') ?> me-2 opacity-75"></i><?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></a>
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
