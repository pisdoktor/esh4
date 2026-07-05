<?php
namespace App\Helpers;

use App\Helpers\AuthHelper;
use App\Models\User;

class UIHelper {

    /** Proje kökündeki tek dosyalı betikler (ilac_rehber_migration.php). */
    public static function projectRootScriptUrl(string $basename): string
    {
        return UrlHelper::projectRootScriptUrl($basename);
    }

    /**
     * Üst menüyü render eder
     */
    public static function renderTopMenu($currentController, $currentAction) {
        $navAvatarUrl = User::defaultProfileImageWebUrl();
        $navUserId = AuthHelper::sessionUserId() ?? '';
        if (!IdHelper::isEmptyEntityId($navUserId)) {
            $navUser = new User();
            if ($navUser->load($navUserId)) {
                $navAvatarUrl = $navUser->profileImageWebUrl();
            }
        }
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
        <div class="container-fluid">
            <a class="navbar-brand fw-bolder d-flex align-items-center" href="<?= htmlspecialchars(esh_url('Dashboard', 'index'), ENT_QUOTES, 'UTF-8') ?>">
                <div class="bg-primary p-2 rounded-3 me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                    <i class="fa-solid fa-house-medical  fs-6"></i>
                </div>
                <span class="tracking-tight">SON<span class="text-primary">EV</span></span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#eshNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="eshNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link px-3 <?= ($currentController == 'Dashboard' && $currentAction == 'index') ? 'active fw-bold border-bottom border-primary border-3' : '' ?>" 
                           href="<?= htmlspecialchars(esh_url('Dashboard', 'index'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid fa-house me-1"></i> Anasayfa
                        </a>
                    </li>

                    <?php if (AuthHelper::can('stats.read') && !AuthHelper::sessionIsAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?= ($currentController == 'Stats') ? 'active fw-bold border-bottom border-info border-3' : '' ?>"
                           href="<?= htmlspecialchars(esh_url('Stats', 'index'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid fa-chart-pie me-1"></i> İstatistikler
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (AuthHelper::can('patient.read')): ?>
                    <li class="nav-item dropdown px-1">
                        <a class="nav-link dropdown-toggle px-3 <?= ($currentController == 'Patient') ? 'active fw-bold border-bottom border-primary border-3' : '' ?>" href="#" id="hastaMenu" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-hospital-user me-1"></i> Hasta İşlemleri
                        </a>
                        <ul class="dropdown-menu shadow-lg border-0 mt-2 py-2 rounded-3" aria-labelledby="hastaMenu">
                            <li><a class="dropdown-item py-2 ps-4 small" href="<?= htmlspecialchars(esh_url('Patient', 'unified', array (
  'status' => 'active',
)), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-user-check text-success me-2 opacity-75"></i>Aktif</a></li>
                            <li><a class="dropdown-item py-2 ps-4 small" href="<?= htmlspecialchars(esh_url('Patient', 'unified', array (
  'status' => 'passive',
)), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-user-slash text-secondary me-2 opacity-75"></i>Pasif</a></li>
                            <li><a class="dropdown-item py-2 ps-4 small" href="<?= htmlspecialchars(esh_url('Patient', 'unified', array (
  'status' => 'waiting',
)), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-user-clock text-info me-2 opacity-75"></i>Bekleyen</a></li>
                            <?php if (AuthHelper::sessionIsAdmin() && \App\Helpers\PatientNakilRequest::tableReady()): ?>
                            <?php
                            $eshNakilPending = 0;
                            if (\App\Helpers\AuthHelper::sessionIsSuperAdmin()) {
                                $eshNakilPending = count(\App\Helpers\PatientNakilRequest::getIncomingList(null));
                            } else {
                                $eshNk = \App\Helpers\TenantContext::sessionKurumId();
                                if ($eshNk !== null && $eshNk > 0) {
                                    $eshNakilPending = \App\Helpers\PatientNakilRequest::countPendingForTargetKurum($eshNk);
                                }
                            }
                            ?>
                            <li><a class="dropdown-item py-2 ps-4 small" href="<?= htmlspecialchars(esh_url('PatientNakil', 'incoming'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-building-circle-arrow-right text-warning me-2 opacity-75"></i>Gelen nakil talepleri<?= $eshNakilPending > 0 ? ' (' . (int) $eshNakilPending . ')' : '' ?></a></li>
                            <?php endif; ?>
                            <?php if (AuthHelper::sessionIsAdmin()): ?>
                            <li><a class="dropdown-item py-2 ps-4 small" href="<?= htmlspecialchars(esh_url('Patient', 'unified', array (
  'status' => 'all',
)), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-list me-2 text-muted opacity-75"></i>Tüm durumlar</a></li>
                            <?php endif; ?>
                            <?php if (AuthHelper::can('patient.create')): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 fw-bold text-primary" href="<?= htmlspecialchars(esh_url('Patient', 'ilkkayit'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-user-plus me-2"></i>Yeni Kayıt</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (AuthHelper::can('visit.read') || AuthHelper::can('planned_visit.read')): ?>
                    <li class="nav-item dropdown px-1">
                        <a class="nav-link dropdown-toggle px-3 <?= ($currentController == 'Visit' || $currentController == 'PlannedVisit') ? 'active fw-bold border-bottom border-primary border-3' : '' ?>" href="#" id="izlemMenu" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-notes-medical me-1"></i> İzlem İşlemleri
                        </a>
                        <ul class="dropdown-menu shadow-lg border-0 mt-2 py-2 rounded-3" aria-labelledby="izlemMenu">
                            <?php if (AuthHelper::can('visit.read')): ?>
                            <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(esh_url('Visit', 'index'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-notes-medical text-success me-2"></i>Aktif İzlemler</a></li>
                            <?php endif; ?>
                            <?php if (AuthHelper::can('planned_visit.read')): ?>
                            <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(esh_url('PlannedVisit', 'index'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-calendar-day text-primary me-2"></i>Planlanmış İzlemler</a></li>
                            <?php endif; ?>
                            <?php if (AuthHelper::sessionIsAdmin()): ?>
                            <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(esh_url('PlannedVisit', 'passivePendingPlans'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-user-clock text-danger me-2"></i>Pasif hasta — bekleyen planlar</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (AppSettings::isModuleEnabled('erapor') && AuthHelper::can('erapor.read')): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?= ($currentController == 'Erapor') ? 'active fw-bold text-info border-bottom border-info border-3' : '' ?>" 
                           href="<?= htmlspecialchars(esh_url('Erapor', 'index'), ENT_QUOTES, 'UTF-8') ?>">
                           <i class="fa-solid fa-file-waveform me-1"></i> e-Rapor
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (AppSettings::isModuleEnabled('ilac_rehber') && AuthHelper::can('ilac_rehber.read')): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?= ($currentController == 'IlacRehber' && $currentAction === 'search') ? 'active fw-bold border-bottom border-primary border-3' : '' ?>"
                           href="<?= htmlspecialchars(esh_url('IlacRehber', 'search'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid fa-book-medical me-1"></i> İlaç Rehberi
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (\App\Services\MesajService::canUseMessaging((AuthHelper::sessionUserId() ?? '')) && AuthHelper::can('mesajlasma.read')): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3 position-relative <?= ($currentController == 'Mesaj') ? 'active fw-bold border-bottom border-primary border-3' : '' ?>"
                           href="<?= htmlspecialchars(esh_url('Mesaj', 'index'), ENT_QUOTES, 'UTF-8') ?>"
                           id="esh-mesaj-nav-link"
                           data-poll-url="<?= htmlspecialchars(esh_url('Mesaj', 'poll'), ENT_QUOTES, 'UTF-8') ?>"
                           title="Mesajlar">
                            <i class="fa-solid fa-envelope me-1"></i> Mesajlar
                            <span id="esh-mesaj-nav-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    $eshTakvimRandevu = AppSettings::isModuleEnabled('randevu');
                    $eshTakvimUhds = AppSettings::isModuleEnabled('uhds');
                    if (($eshTakvimRandevu && AuthHelper::can('randevu.read')) || ($eshTakvimUhds && AuthHelper::can('uhds.read'))):
                    ?>
                    <li class="nav-item dropdown px-1">
                        <a class="nav-link dropdown-toggle px-3 <?= ($currentController == 'Randevu' || $currentController == 'Uhds') ? 'active fw-bold text-info border-bottom border-info border-3' : '' ?>" href="#" id="takvimlerMenu" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-calendar-days me-1"></i> Takvimler
                        </a>
                        <ul class="dropdown-menu shadow-lg border-0 mt-2 py-2 rounded-3" aria-labelledby="takvimlerMenu">
                            <?php if ($eshTakvimRandevu && AuthHelper::can('randevu.read')): ?>
                            <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(esh_url('Randevu', 'index'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-calendar-week text-info me-2"></i>Branş randevu takvimi</a></li>
                            <?php endif; ?>
                            <?php if ($eshTakvimUhds && AuthHelper::can('uhds.read')): ?>
                            <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(esh_url('Uhds', 'index'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-video text-info me-2"></i>Uhds</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <?php if (AuthHelper::sessionIsSuperAdmin() && \App\Models\Kurum::tableExists()): ?>
                    <?php
                    $eshBolgeLocked = \App\Helpers\TenantContext::sessionIsBolgeLockedSuperAdmin();
                    $eshAssignedBolgeId = \App\Helpers\TenantContext::sessionAssignedBolgeId();
                    $eshBolgeFilterActive = \App\Helpers\FederationHelper::enabled()
                        ? \App\Helpers\FederationContext::sessionBolgeFilter()
                        : null;
                    $eshBolgeFilterList = \App\Helpers\FederationHelper::enabled()
                        ? (new \App\Models\FederationRegion())->getList(true)
                        : [];
                    $eshKurumFilterList = \App\Helpers\TenantContext::kurumListForScope(true);
                    $eshKurumFilterActive = \App\Helpers\TenantContext::sessionKurumFilter();
                    ?>
                    <?php if ($eshBolgeLocked && $eshAssignedBolgeId !== null): ?>
                    <li class="nav-item me-2 me-lg-3 d-flex align-items-center">
                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                            <i class="fa-solid fa-map me-1"></i><?= htmlspecialchars(\App\Helpers\FederationHelper::kurumBolgeLabel($eshAssignedBolgeId), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </li>
                    <?php elseif (\App\Helpers\FederationHelper::enabled() && $eshBolgeFilterList !== [] && AuthHelper::sessionIsPlatformOwner()): ?>
                    <li class="nav-item me-2 me-lg-3">
                        <form method="post" action="<?= htmlspecialchars(esh_url('Federation', 'setBolgeFilter'), ENT_QUOTES, 'UTF-8') ?>" class="d-flex align-items-center gap-1">
                            <?= esh_csrf_field() ?>
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <label class="visually-hidden" for="eshBolgeFilter">Bölge filtresi</label>
                            <select name="bolge_id" id="eshBolgeFilter" class="form-select form-select-sm" style="max-width:11rem" data-esh-auto-submit>
                                <option value="0">Tüm bölgeler</option>
                                <?php foreach ($eshBolgeFilterList as $bf): ?>
                                    <option value="<?= (int) ($bf->id ?? 0) ?>"<?= $eshBolgeFilterActive === (int) ($bf->id ?? 0) ? ' selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($bf->ad ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item me-2 me-lg-3">
                        <form method="post" action="<?= htmlspecialchars(esh_url('Kurum', 'setFilter'), ENT_QUOTES, 'UTF-8') ?>" class="d-flex align-items-center gap-1">
                            <?= esh_csrf_field() ?>
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <label class="visually-hidden" for="eshKurumFilter">Kurum filtresi</label>
                            <select name="kurum_id" id="eshKurumFilter" class="form-select form-select-sm" style="max-width:11rem" data-esh-auto-submit>
                                <option value="0">Tüm kurumlar</option>
                                <?php foreach ($eshKurumFilterList as $kf): ?>
                                    <option value="<?= (int) ($kf->id ?? 0) ?>"<?= $eshKurumFilterActive === (int) ($kf->id ?? 0) ? ' selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($kf->ad ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </li>
                    <?php endif; ?>
                    <?php if (AuthHelper::sessionIsAdmin()): ?>
                    <li class="nav-item d-flex align-items-center me-2 me-lg-3">
                        <button type="button"
                                class="btn btn-warning text-dark rounded-pill px-3 fw-bold shadow-sm"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#eshAdminOffcanvas"
                                aria-controls="eshAdminOffcanvas">
                            <i class="fas fa-user-shield me-1"></i> Yönetim
                        </button>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center bg-secondary bg-opacity-10 border border-secondary border-opacity-25 rounded-pill px-3" href="#" 
                           id="userMenu" role="button" data-bs-toggle="dropdown">
                            <img src="<?= htmlspecialchars($navAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                 class="rounded-circle me-2" width="24" height="24" alt="">
                            <span class="small fw-bold"><?= htmlspecialchars((string) ($_SESSION['name'] ?? 'Kullanıcı'), ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 py-2 rounded-3" aria-labelledby="userMenu">
                            <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-circle-user me-2 opacity-75"></i>Profilim</a></li>
                            <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(esh_url('User', 'edit'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-sliders me-2 opacity-75"></i>Hesap Ayarları</a></li>
                            <?php if (\App\Services\MesajService::canUseMessaging((AuthHelper::sessionUserId() ?? ''))): ?>
                            <?php
                                $eshMesajMenuUnread = 0;
                                $eshMesajMenuUserId = (AuthHelper::sessionUserId() ?? '');
                                if ($eshMesajMenuUserId > 0) {
                                    $eshMesajMenuUnread = (new \App\Services\MesajService())->countUnread($eshMesajMenuUserId);
                                }
                                $eshMesajMenuLabel = 'Mesaj Kutusu';
                                if ($eshMesajMenuUnread > 0) {
                                    $eshMesajMenuLabel .= ' (' . ($eshMesajMenuUnread > 99 ? '99+' : (string) (int) $eshMesajMenuUnread) . ')';
                                }
                            ?>
                            <li><a class="dropdown-item py-2<?= ($currentController == 'Mesaj') ? ' active fw-semibold' : '' ?>" href="<?= htmlspecialchars(esh_url('Mesaj', 'index'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-inbox me-2 opacity-75"></i><span id="esh-mesaj-menu-label"><?= htmlspecialchars($eshMesajMenuLabel, ENT_QUOTES, 'UTF-8') ?></span></a></li>
                            <?php endif; ?>
                            <?php
                                $eshNobetMineEnabled = \App\Models\User::canAccessNobetMine();
                                $eshNobetMineActive = ($currentController == 'Nobet' && $currentAction == 'mine');
                            ?>
                            <li><a class="dropdown-item py-2<?= $eshNobetMineActive ? ' active fw-semibold' : '' ?><?= !$eshNobetMineEnabled ? ' disabled' : '' ?>"
                                   href="<?= $eshNobetMineEnabled ? htmlspecialchars(esh_url('Nobet', 'mine'), ENT_QUOTES, 'UTF-8') : '#' ?>"
                                   <?= !$eshNobetMineEnabled ? ' aria-disabled="true" tabindex="-1"' : '' ?>><i class="fa-solid fa-calendar-minus me-2 opacity-75"></i>İzin/Mazeret</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger fw-bold py-2" href="<?= htmlspecialchars(esh_url('Auth', 'logout'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-power-off me-2"></i>Güvenli Çıkış</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php if (AuthHelper::sessionIsAdmin()): ?>
        <?php AdminNavHelper::renderOffcanvas($currentController, $currentAction); ?>
    <?php endif; ?>
    <?php
        self::renderMaintenanceModeBanner();
    }

    /**
     * Bakım modu aktifken sistem yöneticisine navbar altında uyarı şeridi.
     */
    public static function renderMaintenanceModeBanner(): void
    {
        if (!AuthHelper::sessionIsPlatformOwner() || !OperationalSettings::isMaintenanceModeEnabled()) {
            return;
        }
        $settingsUrl = esh_url('Settings', 'index', ['tab' => 'bakim']);
        ?>
    <div class="alert alert-warning border-0 rounded-0 mb-0 py-2 small text-center" role="alert">
        <i class="fa-solid fa-screwdriver-wrench me-1"></i>
        <strong>Bakım modu aktif</strong> — yalnızca <?= htmlspecialchars(mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?> erişebilir.
        <a href="<?= htmlspecialchars($settingsUrl, ENT_QUOTES, 'UTF-8') ?>" class="alert-link ms-1">Ayarları aç</a>
    </div>
        <?php
    }
    
    /**
     * Sıralama linklerine eklenecek limit/page (mevcut istek URL'sinden).
     *
     * @param bool $resetPage true ise page=1 (sütun değişince boş sayfa riskini azaltır)
     */
    public static function sortPaginationQuery(bool $resetPage = false): string
    {
        $parts = [];
        if (isset($_GET['limit'])) {
            $limit = (int) $_GET['limit'];
            if ($limit > 0) {
                $parts[] = 'limit=' . $limit;
            }
        }
        if ($resetPage) {
            $parts[] = 'page=1';
        } elseif (isset($_GET['page'])) {
            $page = (int) $_GET['page'];
            if ($page > 1) {
                $parts[] = 'page=' . $page;
            }
        }

        return $parts === [] ? '' : '&' . implode('&', $parts);
    }

    /**
     * $pagelink + orderby/orderdir + limit/page (istekte varsa).
     */
    public static function sortHrefAppend(string $pagelink, string $field, string $nextDir, bool $resetPage = false): string
    {
        $sep = str_contains($pagelink, '?') ? '&' : '?';

        return $pagelink
            . $sep . 'orderby=' . rawurlencode($field)
            . '&orderdir=' . rawurlencode($nextDir)
            . self::sortPaginationQuery($resetPage);
    }

    /**
     * `h.isim ASC` veya `h.isim-ASC` → `h.isim ASC`
     */
    public static function normalizeOrdering(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^(.+)-(ASC|DESC)$/i', $raw, $m)) {
            return $m[1] . ' ' . strtoupper($m[2]);
        }
        $bits = explode(' ', $raw, 2);
        $field = $bits[0];
        $dir = isset($bits[1]) ? strtoupper(trim($bits[1])) : 'ASC';

        return $field . ' ' . ($dir === 'DESC' ? 'DESC' : 'ASC');
    }

    /**
     * Tablo başlıkları için sıralama ikonu, yön ve sıralama linki eki (limit/page).
     *
     * @return array{icon: string, nextDir: string, suffix: string, href?: string}
     */
    public static function sortIcon(string $field, string $ordering, ?string $pagelink = null, bool $resetPage = false): array
    {
        $normalized = self::normalizeOrdering($ordering);
        $bits = explode(' ', $normalized, 2);
        $currentField = $bits[0] ?? '';
        $currentDir = isset($bits[1]) ? strtoupper($bits[1]) : 'ASC';

        $icon = ' <i class="fa-solid fa-sort text-muted opacity-25"></i>';
        $nextDir = 'ASC';

        if ($field === $currentField) {
            if ($currentDir === 'ASC') {
                $icon = ' <i class="fa-solid fa-sort-up text-primary"></i>';
                $nextDir = 'DESC';
            } else {
                $icon = ' <i class="fa-solid fa-sort-down text-primary"></i>';
                $nextDir = 'ASC';
            }
        }

        $suffix = self::sortPaginationQuery($resetPage);
        $out = ['icon' => $icon, 'nextDir' => $nextDir, 'suffix' => $suffix];
        if ($pagelink !== null && $pagelink !== '') {
            $out['href'] = self::sortHrefAppend($pagelink, $field, $nextDir, $resetPage);
        }

        return $out;
    }

    /**
     * Sıralama URL’leri (toggle / ASC / DESC) ve ikon durumu.
     *
     * @param array{
     *   mode?: 'orderby'|'ordering'|'merge',
     *   pagelink?: string,
     *   base?: array<string, scalar|null>,
     *   mergeBase?: array<string, scalar|null>,
     *   resetPage?: bool,
     *   resetPageOnFieldChange?: bool,
     * } $config
     * @return array{
     *   toggle: string,
     *   asc: string,
     *   desc: string,
     *   icon: string,
     *   nextDir: string,
     *   suffix: string,
     *   isActive: bool,
     *   currentDir: string,
     * }
     */
    public static function sortUrls(string $field, string $ordering, array $config = []): array
    {
        $normalized = self::normalizeOrdering($ordering);
        $bits = explode(' ', $normalized, 2);
        $currentField = $bits[0] ?? '';
        $currentDir = isset($bits[1]) ? strtoupper($bits[1]) : 'ASC';
        $isActive = ($field === $currentField);
        $resetPageFlag = (bool) ($config['resetPage'] ?? false);
        $resetOnChange = (bool) ($config['resetPageOnFieldChange'] ?? true);
        $resetForDir = $resetPageFlag || ($resetOnChange && !$isActive);

        $sortState = self::sortIcon($field, $normalized, null, $resetPageFlag);
        $mode = (string) ($config['mode'] ?? 'orderby');

        $buildUrl = static function (string $dir, bool $reset) use ($field, $mode, $config): string {
            if ($mode === 'ordering') {
                return self::sortUrlWithOrdering($config['base'] ?? [], $field . '-' . $dir, $reset);
            }
            if ($mode === 'merge') {
                $overrides = array_merge($config['mergeBase'] ?? [], [
                    'orderby' => $field,
                    'orderdir' => $dir,
                ]);

                return self::sortUrlMergeGet($overrides, $reset);
            }
            if (!empty($config['base']) && is_array($config['base'])) {
                $q = array_merge($config['base'], [
                    'orderby' => $field,
                    'orderdir' => $dir,
                ]);
                if ($reset) {
                    $q['page'] = '1';
                } elseif (isset($_GET['page'])) {
                    $page = (int) $_GET['page'];
                    if ($page > 1) {
                        $q['page'] = (string) $page;
                    }
                }
                if (isset($_GET['limit'])) {
                    $limit = (int) $_GET['limit'];
                    if ($limit > 0) {
                        $q['limit'] = (string) $limit;
                    }
                }

                return \App\Helpers\UrlHelper::fromRequestParams($q);
            }

            return self::sortHrefAppend((string) ($config['pagelink'] ?? ''), $field, $dir, $reset);
        };

        return [
            'toggle' => $buildUrl($sortState['nextDir'], $resetForDir),
            'asc' => $buildUrl('ASC', $resetForDir),
            'desc' => $buildUrl('DESC', $resetForDir),
            'icon' => $sortState['icon'],
            'nextDir' => $sortState['nextDir'],
            'suffix' => $sortState['suffix'],
            'isActive' => $isActive,
            'currentDir' => $isActive ? $currentDir : '',
        ];
    }

    /**
     * Standart sıralanabilir tablo başlığı (&lt;th&gt; veya yalnızca iç HTML).
     *
     * @param array{
     *   mode?: 'orderby'|'ordering'|'merge',
     *   pagelink?: string,
     *   base?: array<string, scalar|null>,
     *   mergeBase?: array<string, scalar|null>,
     *   resetPage?: bool,
     *   resetPageOnFieldChange?: bool,
     *   thClass?: string,
     *   wrapTh?: bool,
     *   arrows?: bool,
     * } $config
     */
    public static function renderSortTh(string $label, string $field, string $ordering, array $config = []): string
    {
        $urls = self::sortUrls($field, $ordering, $config);
        $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $toggleEsc = htmlspecialchars($urls['toggle'], ENT_QUOTES, 'UTF-8');
        $ascEsc = htmlspecialchars($urls['asc'], ENT_QUOTES, 'UTF-8');
        $descEsc = htmlspecialchars($urls['desc'], ENT_QUOTES, 'UTF-8');
        $showArrows = ($config['arrows'] ?? true) === true;
        // Yan oklar varken başlıkta ayrıca fa-sort ikonu gösterme (üçlü tekrar olmasın).
        $labelIcon = $showArrows ? '' : $urls['icon'];

        $inner = '<span class="esh-ui-table-sort-wrap">'
            . '<a class="esh-ui-table-sort" href="' . $toggleEsc . '">'
            . $labelEsc . $labelIcon
            . '</a>';

        if ($showArrows) {
            $ascActive = $urls['isActive'] && $urls['currentDir'] === 'ASC' ? ' is-active' : '';
            $descActive = $urls['isActive'] && $urls['currentDir'] === 'DESC' ? ' is-active' : '';
            $inner .= '<span class="esh-ui-table-sort-arrows" aria-hidden="true">'
                . '<a class="esh-ui-table-sort-dir' . $ascActive . '" href="' . $ascEsc . '" title="Artan sıralama">'
                . '<i class="fa-solid fa-caret-up"></i></a>'
                . '<a class="esh-ui-table-sort-dir' . $descActive . '" href="' . $descEsc . '" title="Azalan sıralama">'
                . '<i class="fa-solid fa-caret-down"></i></a>'
                . '</span>';
        }

        $inner .= '</span>';

        if (($config['wrapTh'] ?? true) === false) {
            return $inner;
        }

        $thClass = trim((string) ($config['thClass'] ?? ''));
        $thClassAttr = $thClass !== '' ? ' class="' . htmlspecialchars($thClass, ENT_QUOTES, 'UTF-8') . '"' : '';

        return '<th' . $thClassAttr . '>' . $inner . '</th>';
    }

    /**
     * İzlem listesi gibi `ordering=h.isim-ASC` kullanan sayfalar için sıralama URL’si.
     *
     * @param array<string, scalar|null> $base
     */
    public static function sortUrlWithOrdering(array $base, string $orderingToken, bool $resetPage = false): string
    {
        $q = $base;
        $q['ordering'] = $orderingToken;
        if (isset($_GET['limit'])) {
            $limit = (int) $_GET['limit'];
            if ($limit > 0) {
                $q['limit'] = $limit;
            }
        }
        if ($resetPage) {
            $q['page'] = 1;
        } elseif (isset($_GET['page'])) {
            $page = (int) $_GET['page'];
            if ($page > 1) {
                $q['page'] = $page;
            }
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /**
     * e-Rapor vb. orderby/orderdir + filtreleri koruyan sıralama URL’si.
     *
     * @param array<string, scalar|null> $overrides
     */
    public static function sortUrlMergeGet(array $overrides, bool $resetPage = false): string
    {
        $q = $_GET;
        foreach (['orderby', 'orderdir', 'ordering', 'page'] as $drop) {
            unset($q[$drop]);
        }
        $q = array_merge($q, $overrides);
        if (isset($_GET['limit'])) {
            $limit = (int) $_GET['limit'];
            if ($limit > 0) {
                $q['limit'] = $limit;
            }
        }
        if ($resetPage) {
            $q['page'] = 1;
        } elseif (isset($_GET['page'])) {
            $page = (int) $_GET['page'];
            if ($page > 1) {
                $q['page'] = $page;
            }
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /**
     * Hasta listelerinde ortak "özet" mini buton grubu (yapılan / yapılmayan / planlı).
     */
    public static function patientSummaryButtons(string $tc, int $doneCount, int $missedCount, int $plannedCount): string
    {
        $tcQ = rawurlencode($tc);
        $doneHref = esh_url('Visit', 'history', ['tc' => $tc, 'status' => 1]);
        $missedHref = esh_url('Visit', 'history', ['tc' => $tc, 'status' => 0]);
        $plannedHref = esh_url('PlannedVisit', 'patient', ['tc' => $tc]);
        $missedBtn = $missedCount > 0 ? 'btn-danger' : 'btn-outline-secondary';
        $plannedBtn = $plannedCount > 0 ? 'btn-warning text-dark' : 'btn-outline-secondary';

        return
            '<div class="esh-patient-summary-btns d-flex flex-column gap-1 w-100" role="group" aria-label="İzlem özeti">'
            . '<a href="' . htmlspecialchars($doneHref, ENT_QUOTES, 'UTF-8') . '" class="btn btn-sm btn-outline-info py-0 px-1 esh-psb-line" title="Yapılan: ' . $doneCount . '"><span class="esh-psb-n">' . $doneCount . '</span></a>'
            . '<a href="' . htmlspecialchars($missedHref, ENT_QUOTES, 'UTF-8') . '" class="btn btn-sm ' . $missedBtn . ' py-0 px-1 esh-psb-line" title="Yapılmayan"><span class="esh-psb-n">' . $missedCount . '</span></a>'
            . '<a href="' . htmlspecialchars($plannedHref, ENT_QUOTES, 'UTF-8') . '" class="btn btn-sm ' . $plannedBtn . ' py-0 px-1 esh-psb-line" title="Planlı"><span class="esh-psb-n">' . $plannedCount . '</span></a>'
            . '</div>';
    }

    /**
     * Yönetim istatistik tablolarında hasta adı → hasta kartı (`Patient::view`) bağlantısı.
     *
     * @param object $row `#__hastalar` benzeri satır (`id`, `isim`, `soyisim`)
     */
    public static function patientStatsCardLink(object $row, string $extraClasses = ''): string
    {
        $id = IdHelper::normalizeRequestId($row->id ?? null);
        $name = trim((string) ($row->isim ?? '') . ' ' . (string) ($row->soyisim ?? ''));
        $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        if ($id === null) {
            return $nameEsc;
        }
        $href = esh_url('Patient', 'view', ['id' => $id]);
        $cls = trim('link-primary fw-semibold text-decoration-none ' . $extraClasses);

        return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '">' . $nameEsc . '</a>';
    }

    /**
     * Sabah / Öğle / Akşam — ilkkayit.php ile aynı renkli btn-group (outline-warning / danger / primary).
     *
     * @param string $fieldName   Örn. zaman, pzaman
     * @param string $idPrefix    Sayfada benzersiz olacak HTML id öneki
     * @param int|string|null $selected
     * @param bool   $compact     Tablo satırları için daha küçük butonlar
     * @deprecated bool $usePlanValues123 Artık yok sayılır; tüm formlar 1–3 kullanır.
     */
    /**
     * Saate göre izlem zaman dilimi (1 Sabah, 2 Öğle, 3 Akşam).
     */
    public static function zamanDilimiFromHour(?int $hour = null): ?int
    {
        return ZamanDilimiHelper::fromHour($hour);
    }

    public static function zamanDilimiRadios(
        string $fieldName,
        string $idPrefix,
        $selected = null,
        bool $usePlanValues123 = false,
        bool $compact = false
    ): string {
        unset($usePlanValues123);
        $idBase = preg_replace('/[^a-zA-Z0-9_-]/', '', $idPrefix);
        if ($idBase === '') {
            $idBase = 'z';
        }

        $noSelection = $selected === null || $selected === '';
        $s = $noSelection ? null : ZamanDilimiHelper::normalize($selected);

        $py = $compact ? ' py-1' : ' py-2';
        $grp = $compact ? 'btn-group btn-group-sm w-100' : 'btn-group w-100';
        $fn = htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8');

        $blocks = ZamanDilimiHelper::radioBlocks($s, true);

        if ($blocks === []) {
            $blocks = ZamanDilimiHelper::radioBlocks(null, false);
        }

        if (count($blocks) === 1 && empty($blocks[0]['readonly'])) {
            $b = $blocks[0];
            $vid = $idBase . '-t0';
            $checked = ($s === null || (int) $b['v'] === $s) ? ' checked' : '';

            return '<input type="hidden" name="' . $fn . '" value="' . (int) $b['v'] . '">'
                . '<div class="text-muted small"><i class="' . htmlspecialchars($b['icon'], ENT_QUOTES, 'UTF-8') . ' me-1"></i>'
                . htmlspecialchars($b['label'], ENT_QUOTES, 'UTF-8') . '</div>';
        }

        $html = '<div class="' . htmlspecialchars($grp, ENT_QUOTES, 'UTF-8') . '" role="group" aria-label="Zaman dilimi">';
        foreach ($blocks as $idx => $b) {
            $vid = $idBase . '-t' . $idx;
            $checked = ($s !== null && (int) $b['v'] === $s) ? ' checked' : '';
            $readonly = !empty($b['readonly']);
            $disabled = $readonly ? ' disabled' : '';
            $required = $readonly ? '' : ' required';
            $html .= '<input type="radio" class="btn-check" name="' . $fn . '" id="' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '" value="' . (int) $b['v'] . '"' . $checked . $disabled . ' autocomplete="off"' . $required . '>';
            $html .= '<label class="btn btn-' . htmlspecialchars($b['cls'], ENT_QUOTES, 'UTF-8') . $py . ($readonly ? ' opacity-75' : '') . '" for="' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<i class="' . htmlspecialchars($b['icon'], ENT_QUOTES, 'UTF-8') . ' me-1"></i>' . htmlspecialchars($b['label'], ENT_QUOTES, 'UTF-8') . '</label>';
            if ($readonly && $checked !== '') {
                $html .= '<input type="hidden" name="' . $fn . '" value="' . (int) $b['v'] . '">';
            }
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Branş randevu — hasta geldi / gelmedi / belirtilmedi (btn-check grubu).
     *
     * @param int|string|null $selected null=belirtilmedi, 1=geldi, 0=gelmedi
     */
    public static function hastaGeldiRadios(
        string $fieldName = 'hasta_geldi',
        string $idPrefix = 'hasta-geldi',
        $selected = null,
        bool $compact = true,
        bool $autoSubmitForm = false
    ): string {
        $idBase = preg_replace('/[^a-zA-Z0-9_-]/', '', $idPrefix);
        if ($idBase === '') {
            $idBase = 'hg';
        }

        $selKey = 'unset';
        if ($selected !== null && $selected !== '') {
            $i = (int) $selected;
            $selKey = $i === 1 ? '1' : ($i === 0 ? '0' : 'unset');
        }

        $py = $compact ? ' py-1' : ' py-2';
        $grp = $compact
            ? 'btn-group btn-group-sm flex-wrap w-100'
            : 'btn-group flex-wrap w-100';
        $fn = htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8');
        $autoSubmitAttr = $autoSubmitForm ? ' data-esh-auto-submit' : '';

        $blocks = [
            ['key' => 'unset', 'val' => '', 'cls' => 'outline-secondary', 'icon' => 'fa-solid fa-minus', 'label' => 'Belirtilmedi'],
            ['key' => '1', 'val' => '1', 'cls' => 'outline-success', 'icon' => 'fa-solid fa-check', 'label' => 'Geldi'],
            ['key' => '0', 'val' => '0', 'cls' => 'outline-danger', 'icon' => 'fa-solid fa-xmark', 'label' => 'Gelmedi'],
        ];

        $html = '<div class="' . htmlspecialchars($grp, ENT_QUOTES, 'UTF-8') . '" role="group" aria-label="Hasta geldi mi">';
        foreach ($blocks as $idx => $b) {
            $vid = $idBase . '-hg' . $idx;
            $checked = ($b['key'] === $selKey) ? ' checked' : '';
            $valAttr = $b['val'] === '' ? ' value=""' : ' value="' . htmlspecialchars((string) $b['val'], ENT_QUOTES, 'UTF-8') . '"';
            $html .= '<input type="radio" class="btn-check" name="' . $fn . '" id="' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '"' . $valAttr . $checked . ' autocomplete="off"' . $autoSubmitAttr . '>';
            $html .= '<label class="btn btn-' . htmlspecialchars($b['cls'], ENT_QUOTES, 'UTF-8') . $py . '" for="' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<i class="' . htmlspecialchars($b['icon'], ENT_QUOTES, 'UTF-8') . ' me-1"></i>' . htmlspecialchars($b['label'], ENT_QUOTES, 'UTF-8') . '</label>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Uhds — Yapıldı mı? (Belirtilmedi / Yapıldı / Yapılmadı).
     */
    public static function yapildiMiRadios(
        string $fieldName = 'hasta_geldi',
        string $idPrefix = 'yapildi-mi',
        $selected = null,
        bool $compact = true,
        bool $autoSubmitForm = false
    ): string {
        $idBase = preg_replace('/[^a-zA-Z0-9_-]/', '', $idPrefix);
        if ($idBase === '') {
            $idBase = 'ym';
        }

        $selKey = 'unset';
        if ($selected !== null && $selected !== '') {
            $i = (int) $selected;
            $selKey = $i === 1 ? '1' : ($i === 0 ? '0' : 'unset');
        }

        $py = $compact ? ' py-1' : ' py-2';
        $grp = $compact
            ? 'btn-group btn-group-sm flex-wrap w-100'
            : 'btn-group flex-wrap w-100';
        $fn = htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8');
        $autoSubmitAttr = $autoSubmitForm ? ' data-esh-auto-submit' : '';

        $blocks = [
            ['key' => 'unset', 'val' => '', 'cls' => 'outline-secondary', 'icon' => 'fa-solid fa-minus', 'label' => 'Belirtilmedi'],
            ['key' => '1', 'val' => '1', 'cls' => 'outline-success', 'icon' => 'fa-solid fa-check', 'label' => 'Yapıldı'],
            ['key' => '0', 'val' => '0', 'cls' => 'outline-danger', 'icon' => 'fa-solid fa-xmark', 'label' => 'Yapılmadı'],
        ];

        $html = '<div class="' . htmlspecialchars($grp, ENT_QUOTES, 'UTF-8') . '" role="group" aria-label="Yapıldı mı">';
        foreach ($blocks as $idx => $b) {
            $vid = $idBase . '-ym' . $idx;
            $checked = ($b['key'] === $selKey) ? ' checked' : '';
            $valAttr = $b['val'] === '' ? ' value=""' : ' value="' . htmlspecialchars((string) $b['val'], ENT_QUOTES, 'UTF-8') . '"';
            $html .= '<input type="radio" class="btn-check" name="' . $fn . '" id="' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '"' . $valAttr . $checked . ' autocomplete="off"' . $autoSubmitAttr . '>';
            $html .= '<label class="btn btn-' . htmlspecialchars($b['cls'], ENT_QUOTES, 'UTF-8') . $py . '" for="' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<i class="' . htmlspecialchars($b['icon'], ENT_QUOTES, 'UTF-8') . ' me-1"></i>' . htmlspecialchars($b['label'], ENT_QUOTES, 'UTF-8') . '</label>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Planlı izlem önceliği (1–3): renkli btn-group — plan formu ile uyumlu.
     *
     * @param int|string|null $selected 1 Normal, 2 Orta, 3 Yüksek (varsayılan 1)
     */
    public static function planOncelikRadios(string $fieldName, string $idPrefix, $selected = 1): string {
        $idBase = preg_replace('/[^a-zA-Z0-9_-]/', '', $idPrefix);
        if ($idBase === '') {
            $idBase = 'onc';
        }
        $s = $selected === null || $selected === '' ? 1 : (int) $selected;
        if ($s < 1 || $s > 3) {
            $s = 1;
        }

        $fn = htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8');
        $grp = 'btn-group w-100';

        $blocks = [
            ['v' => 1, 'cls' => 'outline-success', 'icon' => 'fa-circle-check', 'label' => 'Normal'],
            ['v' => 2, 'cls' => 'outline-warning', 'icon' => 'fa-triangle-exclamation', 'label' => 'Orta (Öncelikli)'],
            ['v' => 3, 'cls' => 'outline-danger', 'icon' => 'fa-bolt', 'label' => 'Yüksek (Acil)'],
        ];

        $html = '<div class="' . htmlspecialchars($grp, ENT_QUOTES, 'UTF-8') . '" role="group" aria-label="Öncelik">';
        foreach ($blocks as $idx => $b) {
            $vid = $idBase . '-o' . $idx;
            $checked = ((int) $b['v'] === $s) ? ' checked' : '';
            $html .= '<input type="radio" class="btn-check" name="' . $fn . '" id="' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '" value="' . (int) $b['v'] . '"' . $checked . ' autocomplete="off">';
            $html .= '<label class="btn btn-' . htmlspecialchars($b['cls'], ENT_QUOTES, 'UTF-8') . ' py-2" for="' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<i class="fa-solid ' . htmlspecialchars($b['icon'], ENT_QUOTES, 'UTF-8') . ' me-1"></i>' . htmlspecialchars($b['label'], ENT_QUOTES, 'UTF-8') . '</label>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Rota planı ekip kartı: öncelik 2 (Orta / öncelikli) için uyarı ikonu.
     */
    public static function planOncelikRouteIcon($oncelik): string
    {
        if ((int) $oncelik !== 2) {
            return '';
        }
        return '<i class="fa-solid fa-triangle-exclamation text-warning ms-1" title="Öncelikli plan" aria-label="Öncelikli plan"></i>';
    }

    /**
     * İzlem kaydı durumu: Yapıldı (1) / Yapılmadı (0) — renkli btn-group.
     */
    public static function izlemYapildimiRadios(string $fieldName, string $idPrefix, int $selected = 1): string {
        $idBase = preg_replace('/[^a-zA-Z0-9_-]/', '', $idPrefix);
        if ($idBase === '') {
            $idBase = 'yap';
        }
        $s = ((int) $selected === 0) ? 0 : 1;

        $fn = htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8');
        $grp = 'btn-group w-100';

        $blocks = [
            ['v' => 1, 'cls' => 'outline-success', 'icon' => 'fa-solid fa-check-double', 'label' => 'Yapıldı'],
            ['v' => 0, 'cls' => 'outline-danger', 'icon' => 'fa-solid fa-hourglass-half', 'label' => 'Yapılmadı'],
        ];

        $html = '<div class="' . htmlspecialchars($grp, ENT_QUOTES, 'UTF-8') . '" role="group" aria-label="İzlem durumu">';
        foreach ($blocks as $idx => $b) {
            $vid = $idBase . '-y' . $idx;
            $checked = ((int) $b['v'] === $s) ? ' checked' : '';
            $html .= '<input type="radio" class="btn-check" name="' . $fn . '" id="' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '" value="' . (int) $b['v'] . '"' . $checked . ' autocomplete="off">';
            $html .= '<label class="btn btn-' . htmlspecialchars($b['cls'], ENT_QUOTES, 'UTF-8') . ' py-2" for="' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<i class="' . htmlspecialchars($b['icon'], ENT_QUOTES, 'UTF-8') . ' me-1"></i>' . htmlspecialchars($b['label'], ENT_QUOTES, 'UTF-8') . '</label>';
        }
        $html .= '</div>';

        return $html;
    }
}
