<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\IdHelper;
use App\Helpers\FederationHelper;
use App\Helpers\PostAllowlistHelper;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Helpers\UserProfileStatsHelper;
use App\Models\FederationRegion;
use App\Models\User;
use App\Models\Kurum;
use App\Models\UserProfileStats;
use App\Services\PermissionService;

class UserController {
    // ==========================================================
    // SITE / KULLANICI BÖLÜMÜ (Kendi Profilini Yönetme)
    // ==========================================================

    /**
     * Kullanıcının kendi profil sayfasını gösterir
     */
    public function index() {
        $userId = $_SESSION['user_id']; // Sadece oturumdaki ID
        $userModel = new User();
        $userModel->load($userId);
        $registerDate = !empty($userModel->registerDate) ? new \DateTime((string) $userModel->registerDate) : new \DateTime();
        $today = new \DateTime();
        $daysWithUs = (int) $registerDate->diff($today)->days;
        $profileStatsFetchUrl = esh_url('User', 'profileStatsContent');

        $user = $userModel;
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'user/index'); 
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Kullanici profilindeki "is ozeti" kartlarini JSON HTML parcasi olarak dondurur.
     */
    public function profileStatsContent() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $userId = AuthHelper::sessionUserId();
        $profileStats = (new User())->getProfileStats((string) $userId);

        ob_start();
        include ROOT_PATH . '/views/site/user/partials/profile_stats_cards.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * İş özeti — istatistik merkezi (tüm metriklere bağlantılar).
     */
    public function stats(): void
    {
        [$userId, $statsAdminView, $statsSubjectUser] = $this->resolveStatsTargetUserId();
        $statsQueryUserId = $statsAdminView ? $userId : null;
        $profileStats = (new User())->getProfileStats($userId);
        $hubGroups = UserProfileStatsHelper::hubGroups();
        $statsHubUrl = UserProfileStatsHelper::statsHubUrl($statsQueryUserId);
        $profileUrl = $statsAdminView
            ? esh_url('User', 'list')
            : esh_url('User', 'index');
        $statsSubjectName = $statsSubjectUser !== null
            ? trim((string) ($statsSubjectUser->name ?? ''))
            : '';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'user/stats_hub');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * İş özeti — tek metrik detay listesi (sayfalı).
     */
    public function statsDetail(): void
    {
        [$userId, $statsAdminView, $statsSubjectUser] = $this->resolveStatsTargetUserId();
        $statsQueryUserId = $statsAdminView ? $userId : null;
        $metricKey = isset($_GET['metric']) ? trim((string) $_GET['metric']) : '';
        $metric = UserProfileStatsHelper::metric($metricKey);
        if ($metric === null) {
            $_SESSION['error'] = 'Geçersiz istatistik türü.';
            header('Location: ' . UserProfileStatsHelper::statsHubUrl($statsQueryUserId));
            exit;
        }

        $limit = isset($_GET['limit']) ? max(10, min(200, (int) $_GET['limit'])) : 50;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        $statsModel = new UserProfileStats();
        $total = $statsModel->countDetail($userId, $metricKey);
        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;
        $rows = $statsModel->getDetailRows($userId, $metricKey, $limit, $offset);

        $profileStats = (new User())->getProfileStats($userId);
        $summaryCount = (int) ($profileStats[$metric['stat_key']] ?? $total);

        $pagelinkQuery = [
            'controller' => 'User',
            'action' => 'statsDetail',
            'metric' => $metricKey,
            'limit' => $limit,
        ];
        if ($statsQueryUserId !== null) {
            $pagelinkQuery['user_id'] = $statsQueryUserId;
        }
        $pagelink = \App\Helpers\UrlHelper::fromRequestParams($pagelinkQuery);
        $statsHubUrl = UserProfileStatsHelper::statsHubUrl($statsQueryUserId);
        $profileUrl = $statsAdminView
            ? esh_url('User', 'list')
            : esh_url('User', 'index');
        $statsSubjectName = $statsSubjectUser !== null
            ? trim((string) ($statsSubjectUser->name ?? ''))
            : '';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'user/stats_detail');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * İstatistik hedef kullanıcı: oturum kullanıcısı veya (yönetici) GET user_id.
     *
     * @return array{0: string, 1: bool, 2: ?User}
     */
    private function resolveStatsTargetUserId(): array
    {
        $sessionId = $this->requireSessionUserId();
        $requested = IdHelper::normalizeRequestId($_GET['user_id'] ?? null);
        if ($requested === null) {
            return [$sessionId, false, null];
        }
        if (IdHelper::idsMatch($requested, $sessionId)) {
            return [$sessionId, false, null];
        }
        if (!AuthHelper::sessionIsAdmin()) {
            $_SESSION['error'] = 'Başka kullanıcının istatistiklerini görme yetkiniz yok.';
            header('Location: ' . esh_url('User', 'index'));
            exit;
        }
        $subject = new User();
        if (!$subject->load($requested)) {
            $_SESSION['error'] = 'Kullanıcı bulunamadı.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        return [$requested, true, $subject];
    }

    private function requireSessionUserId(): string
    {
        $uid = AuthHelper::sessionUserId();
        if ($uid === null) {
            $_SESSION['error'] = 'Oturum gerekli.';
            header('Location: ' . esh_url('Auth', 'login'));
            exit;
        }

        return $uid;
    }

    /**
     * Kullanıcının kendi profilini düzenleme formu
     */
    public function edit() {
        $userId = $_SESSION['user_id'];
        $userModel = new User();
        $userModel->load($userId);

        $themesMeta = ThemeViewHelper::discoverThemesMeta();
        $user = $userModel;
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'user/edit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Kullanıcının kendi bilgilerini güncellemesi (POST)
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            $userModel = new User();
            $userModel->load($userId);

            $isAdmin = AuthHelper::sessionIsAdmin();
            $allowed = ['name', 'email', 'tckimlikno', 'ui_theme'];
            if ($isAdmin) {
                $allowed[] = 'username';
                $allowed[] = 'unvan';
            }
            $data = PostAllowlistHelper::pick($_POST, $allowed);

            // Şifre güncelleme kontrolü
            if (!empty($_POST['new_password'])) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $data['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                } else {
                    $_SESSION['error'] = "Şifreler uyuşmuyor!";
                    header('Location: ' . esh_url('User', 'edit'));
                    exit;
                }
            }

            if ($isAdmin) {
                $data['unvan'] = User::normalizeUnvan($data['unvan'] ?? null);
            }

            $syncUiTheme = false;
            $normalizedUiTheme = null;
            if (array_key_exists('ui_theme', $data)) {
                $syncUiTheme = true;
                $normalizedUiTheme = ThemeViewHelper::normalizeUserUiThemeInput($data['ui_theme'] ?? '');
                $data['ui_theme'] = $normalizedUiTheme;
            }

            if ($userModel->updateProfile($userId, $data)) {
                if ($syncUiTheme) {
                    ThemeViewHelper::syncSessionUserThemeAfterProfileSave($userId, $normalizedUiTheme);
                }
                $_SESSION['success'] = "Profil bilgileriniz güncellendi.";
            } else {
                $_SESSION['error'] = "Güncelleme sırasında bir hata oluştu.";
            }

            header('Location: ' . esh_url('User', 'index'));
            exit;
        }
    }

    // ==========================================================
    // ADMIN BÖLÜMÜ (Tüm Kullanıcıları Yönetme)
    // ==========================================================

    /**
     * @return array{
     *   activatedFilter: ?int,
     *   isadminFilter: ?int,
     *   unvanEmptyOnly: bool,
     *   unvanCode: ?string,
     *   eshUserListActivated: string,
     *   eshUserListRole: string,
     *   eshUserListUnvan: string,
     *   kurumIdFilter: ?int,
     *   bolgeIdFilter: ?int,
     *   eshUserListKurum: string,
     *   eshUserListBolge: string,
     *   filterExpanded: bool
     * }
     */
    private function listFilterState(): array {
        $actRaw = isset($_GET['activated']) ? (string) $_GET['activated'] : '';
        $activatedFilter = null;
        if ($actRaw === '1') {
            $activatedFilter = 1;
        } elseif ($actRaw === '0') {
            $activatedFilter = 0;
        }

        $roleRaw = isset($_GET['role']) ? (string) $_GET['role'] : '';
        $isadminFilter = null;
        if ($roleRaw === 'admin' || $roleRaw === '1') {
            $isadminFilter = 1;
        } elseif ($roleRaw === 'superadmin' || $roleRaw === '2') {
            $isadminFilter = 2;
        } elseif ($roleRaw === 'platform_owner' || $roleRaw === '3') {
            $isadminFilter = 3;
        } elseif ($roleRaw === 'staff' || $roleRaw === '0') {
            $isadminFilter = 0;
        }
        if ($isadminFilter === 3 && !AuthHelper::sessionIsPlatformOwner()) {
            $isadminFilter = null;
        }
        if ($isadminFilter === 2 && !AuthHelper::sessionIsSuperAdmin()) {
            $isadminFilter = null;
        }

        $unvanRaw = isset($_GET['unvan']) ? trim((string) $_GET['unvan']) : '';
        $unvanEmptyOnly = false;
        $unvanCode = null;
        if ($unvanRaw === '__none') {
            $unvanEmptyOnly = true;
        } elseif ($unvanRaw !== '') {
            $normalizedUnvan = User::normalizeUnvan($unvanRaw);
            if ($normalizedUnvan !== null) {
                $unvanCode = $normalizedUnvan;
            }
        }

        $kurumIdFilter = null;
        $eshUserListKurum = '';
        $bolgeIdFilter = null;
        $eshUserListBolge = '';
        if (AuthHelper::sessionIsPlatformOwner() && \App\Helpers\FederationHelper::columnsReady()) {
            $bolgeRaw = isset($_GET['bolge_id']) ? trim((string) $_GET['bolge_id']) : '';
            if ($bolgeRaw !== '' && ctype_digit($bolgeRaw) && (int) $bolgeRaw > 0) {
                $bolgeIdFilter = (int) $bolgeRaw;
                $eshUserListBolge = $bolgeRaw;
            }
        }
        if (AuthHelper::sessionIsSuperAdmin()) {
            $kurumRaw = isset($_GET['kurum_id']) ? trim((string) $_GET['kurum_id']) : '';
            if ($kurumRaw !== '' && ctype_digit($kurumRaw) && (int) $kurumRaw > 0) {
                $candidateKurumId = (int) $kurumRaw;
                if (TenantContext::isKurumInScope($candidateKurumId)) {
                    if ($bolgeIdFilter !== null && $bolgeIdFilter > 0) {
                        $kurumModel = new Kurum();
                        if ($kurumModel->load($candidateKurumId)
                            && isset($kurumModel->bolge_id)
                            && (int) $kurumModel->bolge_id === $bolgeIdFilter) {
                            $kurumIdFilter = $candidateKurumId;
                            $eshUserListKurum = $kurumRaw;
                        }
                    } else {
                        $kurumIdFilter = $candidateKurumId;
                        $eshUserListKurum = $kurumRaw;
                    }
                }
            }
        }

        $eshUserListActivated = $activatedFilter === 1 || $activatedFilter === 0 ? (string) $activatedFilter : '';
        $eshUserListRole = $isadminFilter === 3 ? 'platform_owner' : ($isadminFilter === 2 ? 'superadmin' : ($isadminFilter === 1 ? 'admin' : ($isadminFilter === 0 ? 'staff' : '')));
        $eshUserListUnvan = $unvanEmptyOnly ? '__none' : ($unvanCode ?? '');
        $filterExpanded = $activatedFilter !== null
            || $isadminFilter !== null
            || $unvanEmptyOnly
            || $unvanCode !== null
            || $kurumIdFilter !== null
            || $bolgeIdFilter !== null;

        return [
            'activatedFilter' => $activatedFilter,
            'isadminFilter' => $isadminFilter,
            'unvanEmptyOnly' => $unvanEmptyOnly,
            'unvanCode' => $unvanCode,
            'kurumIdFilter' => $kurumIdFilter,
            'bolgeIdFilter' => $bolgeIdFilter,
            'eshUserListActivated' => $eshUserListActivated,
            'eshUserListRole' => $eshUserListRole,
            'eshUserListUnvan' => $eshUserListUnvan,
            'eshUserListKurum' => $eshUserListKurum,
            'eshUserListBolge' => $eshUserListBolge,
            'filterExpanded' => $filterExpanded,
        ];
    }

    private function listSortState(): array {
        return \App\Helpers\QueryHelper::catalogSort(
            [
                'name' => 'u.name',
                'username' => 'u.username',
                'email' => 'u.email',
                'unvan' => 'u.unvan',
                'kurum' => 'k.ad',
                'activated' => 'u.activated',
            ],
            'name',
            'ASC'
        );
    }

    /**
     * @param array{eshUserListActivated: string, eshUserListRole: string, eshUserListUnvan: string, eshUserListKurum: string, orderby: string, orderdir: string} $state
     */
    private function buildListRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'User',
            'action' => 'listRows',
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        if ($state['eshUserListActivated'] !== '') {
            $q['activated'] = $state['eshUserListActivated'];
        }
        if ($state['eshUserListRole'] !== '') {
            $q['role'] = $state['eshUserListRole'];
        }
        if ($state['eshUserListUnvan'] !== '') {
            $q['unvan'] = $state['eshUserListUnvan'];
        }
        if (($state['eshUserListKurum'] ?? '') !== '') {
            $q['kurum_id'] = $state['eshUserListKurum'];
        }
        if (($state['eshUserListBolge'] ?? '') !== '') {
            $q['bolge_id'] = $state['eshUserListBolge'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /**
     * Admin: Tüm kullanıcıların listesi
     */
    public function list() {
        AuthHelper::requireAdmin();
        $state = $this->listFilterState();
        $sort = $this->listSortState();
        $state = array_merge($state, $sort);
        $eshUserListActivated = $state['eshUserListActivated'];
        $eshUserListRole = $state['eshUserListRole'];
        $eshUserListUnvan = $state['eshUserListUnvan'];
        $eshUserListKurum = $state['eshUserListKurum'];
        $eshUserListBolge = $state['eshUserListBolge'];
        $filterExpanded = $state['filterExpanded'];
        $listRowsFetchUrl = $this->buildListRowsFetchUrl($state);
        $pagelink = esh_url('User', 'list');
        $ordering = trim($sort['orderby'] . ' ' . $sort['orderdir']);
        $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];

        $pageTitle = 'Kullanıcı / Personel Yönetimi';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'user/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Admin kullanıcı listesi tablo satırları (JSON HTML parçası).
     */
    public function listRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!AuthHelper::sessionIsAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Yetkisiz'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->listFilterState();
        $sort = $this->listSortState();
        $items = (new User())->getAdminList(
            $state['activatedFilter'],
            $state['isadminFilter'],
            $state['unvanEmptyOnly'],
            $state['unvanCode'],
            $state['kurumIdFilter'],
            $state['bolgeIdFilter'] ?? null,
            $sort['orderFragment']
        );

        ob_start();
        include ROOT_PATH . '/views/admin/user/partials/list_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Admin: Yeni kullanıcı ekleme formu
     */
    public function create() {
        AuthHelper::requireSuperAdmin();
        $pageTitle = "Yeni Personel Ekle";
        $themesMeta = ThemeViewHelper::discoverThemesMeta();
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'user/create');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Admin: Bir kullanıcıyı düzenleme formu (Dışarıdan ID alır)
     */
    public function adminEdit() {
        AuthHelper::requireAdmin();
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }
        if (!AuthHelper::canManageUser($id)) {
            $_SESSION['error'] = 'Bu kullanıcıyı düzenleme yetkiniz yok.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        $userModel = new User();
        $userModel->load($id);
        $user = $userModel;

        $themesMeta = ThemeViewHelper::discoverThemesMeta();

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'user/edit'); 
        include ThemeViewHelper::resolvePartial('footer');
    }

    /** Süper yönetici: personel kuruma nakil formu. */
    public function changeKurum(): void
    {
        AuthHelper::requireSuperAdmin();
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz kullanıcı.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        $userModel = new User();
        if (!$userModel->load($id)) {
            $_SESSION['error'] = 'Kullanıcı bulunamadı.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        if (AuthHelper::isPlatformLevel((int) ($userModel->isadmin ?? 0))) {
            $_SESSION['error'] = 'Platform hesapları kuruma nakil edilemez.';
            header('Location: ' . esh_url('User', 'adminEdit', ['id' => $id]));
            exit;
        }

        if (\App\Helpers\UserKurumTransfer::isArchivedAtSource($userModel)) {
            $_SESSION['error'] = 'Bu hesap zaten nakil edilmiş (arşiv).';
            header('Location: ' . esh_url('User', 'adminEdit', ['id' => $id]));
            exit;
        }

        $user = $userModel;
        $kurumlar = \App\Models\Kurum::tableExists() ? \App\Helpers\TenantContext::kurumListForScope(true) : [];
        $hasOtherKurum = false;
        foreach ($kurumlar as $k) {
            if ((int) ($k->id ?? 0) !== (int) ($user->kurum_id ?? 0)) {
                $hasOtherKurum = true;
                break;
            }
        }
        if (!$hasOtherKurum) {
            $_SESSION['error'] = 'Nakil için başka aktif kurum bulunmuyor.';
            header('Location: ' . esh_url('User', 'adminEdit', ['id' => $id]));
            exit;
        }
        $currentKurum = null;
        $kid = (int) ($user->kurum_id ?? 0);
        if ($kid > 0) {
            $k = new \App\Models\Kurum();
            if ($k->load($kid)) {
                $currentKurum = $k;
            }
        }

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'user/change_kurum');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /** Süper yönetici: personel kuruma nakil kaydet. */
    public function storeKurum(): void
    {
        AuthHelper::requireSuperAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        if (!\App\Helpers\CsrfHelper::validate()) {
            $_SESSION['error'] = 'Güvenlik doğrulaması başarısız.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $newKid = isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : 0;
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz kullanıcı.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        $userModel = new User();
        if (!$userModel->load($id)) {
            $_SESSION['error'] = 'Kullanıcı bulunamadı.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        $redirect = esh_url('User', 'changeKurum', ['id' => $id]);
        $copyRole = isset($_POST['copy_role']) && (string) $_POST['copy_role'] === '1';
        $newIsadmin = null;
        if (!$copyRole && isset($_POST['isadmin_level'])) {
            $newIsadmin = (int) $_POST['isadmin_level'];
        }

        $err = \App\Helpers\UserKurumTransfer::validate($userModel, $newKid, $newIsadmin);
        if ($err !== null) {
            $_SESSION['error'] = $err;
            header('Location: ' . $redirect);
            exit;
        }

        if ((int) ($userModel->kurum_id ?? 0) === $newKid) {
            $_SESSION['success'] = 'Kurum zaten seçili kurum ile aynı.';
            header('Location: ' . esh_url('User', 'adminEdit', ['id' => $id]));
            exit;
        }

        $transferResult = \App\Helpers\UserKurumTransfer::apply($userModel, $newKid, $newIsadmin);
        if ($transferResult === false) {
            $_SESSION['error'] = 'Kurum nakli sırasında bir hata oluştu.';
            header('Location: ' . $redirect);
            exit;
        }

        $newUserId = is_string($transferResult) ? $transferResult : $id;
        $_SESSION['success'] = 'Personel hedef kurumda açıldı; kaynak kurumdaki hesap pasifleştirildi (Başka Kuruma Nakil).';
        header('Location: ' . esh_url('User', 'adminEdit', ['id' => $newUserId]));
        exit;
    }

    /**
     * Admin: Kullanıcı kaydetme veya güncelleme işlemi
     */
    public function store() {
        AuthHelper::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        $targetId = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $isUpdate = $targetId !== null;
        if (!$isUpdate && !AuthHelper::sessionIsSuperAdmin()) {
            $_SESSION['error'] = 'Yeni kullanıcı oluşturma yetkisi yalnızca '
                . AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN)
                . ' rolüne aittir.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }
        if ($isUpdate && !AuthHelper::canManageUser($targetId)) {
            $_SESSION['error'] = 'Bu kullanıcıyı kaydetme yetkiniz yok.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        $model = new User();
        $existingTarget = null;
        $previousLevel = AuthHelper::ROLE_STAFF;
        if ($isUpdate) {
            $model->load($targetId);
            $existingTarget = $model;
            $previousLevel = AuthHelper::clampLevel((int) $model->isadmin);
        }

        if (!empty($_POST['password'])) {
            $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        } elseif ($isUpdate) {
            unset($_POST['password']);
        }

        unset($_POST['isadmin']);
        $requestedLevel = isset($_POST['isadmin_level']) ? (int) $_POST['isadmin_level'] : AuthHelper::ROLE_STAFF;
        $newLevel = AuthHelper::normalizeIsadminForStore($requestedLevel, $existingTarget);
        if ($requestedLevel >= AuthHelper::ROLE_SUPERADMIN && $newLevel < AuthHelper::ROLE_SUPERADMIN) {
            $_SESSION['error'] = 'Platform hesabı oluşturmak veya atamak için '
                . AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER)
                . ' olarak oturum açmalısınız.';
            header('Location: ' . ($isUpdate ? esh_url('User', 'adminEdit', ['id' => $targetId]) : esh_url('User', 'create')));
            exit;
        }
        if ($newLevel === AuthHelper::ROLE_PLATFORM_OWNER && !AuthHelper::canAssignPlatformOwnerRole()) {
            $_SESSION['error'] = AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER)
                . ' hesabı oluşturma yetkiniz bulunmamaktadır.';
            header('Location: ' . ($isUpdate ? esh_url('User', 'adminEdit', ['id' => $targetId]) : esh_url('User', 'create')));
            exit;
        }
        if ($newLevel === AuthHelper::ROLE_SUPERADMIN && !AuthHelper::canAssignSuperAdminRole()) {
            $_SESSION['error'] = AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN)
                . ' hesabı oluşturma yetkiniz bulunmamaktadır.';
            header('Location: ' . ($isUpdate ? esh_url('User', 'adminEdit', ['id' => $targetId]) : esh_url('User', 'create')));
            exit;
        }
        if ($newLevel === AuthHelper::ROLE_SUPERADMIN
            && AuthHelper::sessionIsPlatformOwner()
            && FederationHelper::columnsReady()
            && FederationHelper::enabled()) {
            $bolgeRaw = $_POST['bolge_id'] ?? '';
            $bid = is_string($bolgeRaw) || is_numeric($bolgeRaw) ? (int) $bolgeRaw : 0;
            if ($bid <= 0) {
                $_SESSION['error'] = AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN)
                    . ' için bir bölge seçmelisiniz.';
                header('Location: ' . ($isUpdate ? esh_url('User', 'adminEdit', ['id' => $targetId]) : esh_url('User', 'create')));
                exit;
            }
            $region = new FederationRegion();
            if (!$region->load($bid) || (int) ($region->aktif ?? 0) !== 1) {
                $_SESSION['error'] = 'Geçerli bir aktif bölge seçmelisiniz.';
                header('Location: ' . ($isUpdate ? esh_url('User', 'adminEdit', ['id' => $targetId]) : esh_url('User', 'create')));
                exit;
            }
        }
        if ($previousLevel === AuthHelper::ROLE_PLATFORM_OWNER
            && $newLevel < AuthHelper::ROLE_PLATFORM_OWNER
            && AuthHelper::countPlatformOwners() <= 1) {
            $_SESSION['error'] = 'Sistemdeki son '
                . AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER)
                . ' rolü düşürülemez.';
            header('Location: ' . ($isUpdate ? esh_url('User', 'adminEdit', ['id' => $targetId]) : esh_url('User', 'create')));
            exit;
        }
        if ($previousLevel === AuthHelper::ROLE_SUPERADMIN
            && $newLevel < AuthHelper::ROLE_SUPERADMIN
            && AuthHelper::countSuperadmins() <= 1) {
            $_SESSION['error'] = 'Sistemdeki son '
                . AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN)
                . ' rolü düşürülemez.';
            header('Location: ' . ($isUpdate ? esh_url('User', 'adminEdit', ['id' => $targetId]) : esh_url('User', 'create')));
            exit;
        }

        $_POST['isadmin'] = $newLevel;
        unset($_POST['isadmin_level']);
        $roleOverrideId = isset($_POST['role_override_id']) ? (int) $_POST['role_override_id'] : 0;
        unset($_POST['role_override_id']);
        unset($_POST['platform_role_id']);
        if (AuthHelper::isPlatformLevel($newLevel)) {
            unset($_POST['kurum_id']);
        }
        if (!(AuthHelper::sessionIsPlatformOwner() && $newLevel === AuthHelper::ROLE_SUPERADMIN)) {
            unset($_POST['bolge_id']);
        }
        $_POST['activated'] = isset($_POST['activated']) ? 1 : 0;
        $_POST['eimza_enabled'] = isset($_POST['eimza_enabled']) ? 1 : 0;

        $_POST['unvan'] = User::normalizeUnvan($_POST['unvan'] ?? null);

        if (array_key_exists('ui_theme', $_POST)) {
            $_POST['ui_theme'] = ThemeViewHelper::normalizeUserUiThemeInput($_POST['ui_theme'] ?? '');
        }

        $model->bind($_POST);

        if ($newLevel >= AuthHelper::ROLE_PLATFORM_OWNER) {
            $model->set('kurum_id', null);
            $model->set('bolge_id', null);
        } elseif ($newLevel === AuthHelper::ROLE_SUPERADMIN) {
            $model->set('kurum_id', null);
            if (AuthHelper::sessionIsPlatformOwner()) {
                $bolgeRaw = $_POST['bolge_id'] ?? '';
                $bid = is_string($bolgeRaw) || is_numeric($bolgeRaw) ? (int) $bolgeRaw : 0;
                $model->set('bolge_id', $bid > 0 ? $bid : null);
            }
        } else {
            $model->set('bolge_id', null);
            if (AuthHelper::sessionIsSuperAdmin()) {
                $reqKid = isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : 0;
                if ($reqKid <= 0 || !TenantContext::isKurumInScope($reqKid)) {
                    $_SESSION['error'] = 'Personel ve kurum yöneticisi için geçerli bir kurum seçmelisiniz.';
                    header('Location: ' . ($isUpdate ? esh_url('User', 'adminEdit', ['id' => $targetId]) : esh_url('User', 'create')));
                    exit;
                }
                $model->set('kurum_id', $reqKid);
            } else {
                $model->set('kurum_id', TenantContext::requireKurumScope());
            }
        }

        if ($model->store()) {
            $savedId = $isUpdate ? $targetId : IdHelper::normalizeRequestId($model->id ?? null);
            if ($savedId !== null && $newLevel === AuthHelper::ROLE_STAFF && PermissionService::tablesReady()) {
                if ($roleOverrideId > 0 && AuthHelper::sessionIsSuperAdmin()) {
                    PermissionService::assignRoleToUser($savedId, $roleOverrideId);
                } else {
                    PermissionService::syncUserRoleFromUnvan($savedId, isset($model->unvan) ? (string) $model->unvan : null);
                }
                if (IdHelper::idsMatch($savedId, AuthHelper::sessionUserId())) {
                    PermissionService::syncSessionPermissions($savedId, $newLevel);
                }
            }
            if ($savedId !== null && IdHelper::idsMatch($savedId, AuthHelper::sessionUserId())) {
                $savedLevel = AuthHelper::clampLevel((int) $model->isadmin);
                AuthHelper::syncSessionFromLevel($savedLevel);
                PermissionService::syncSessionPermissions($savedId, $savedLevel);
                TenantContext::syncSessionFromUser(
                    isset($model->kurum_id) ? (int) $model->kurum_id : null,
                    $savedLevel,
                    isset($model->bolge_id) ? (int) $model->bolge_id : null
                );
                ThemeViewHelper::syncSessionUserThemeAfterProfileSave($savedId, $model->ui_theme ?? null);
            }
            $_SESSION['success'] = 'Kullanıcı başarıyla kaydedildi.';
            if ($isUpdate) {
                \App\Helpers\AuditLogHelper::userUpdate($model);
            } else {
                \App\Helpers\AuditLogHelper::userCreate($model);
            }
        } else {
            $dbErr = trim($model->db->getErrorMsg());
            if ($dbErr !== '' && (AuthHelper::sessionIsSuperAdmin() || (defined('DB_DEBUG') && DB_DEBUG))) {
                $_SESSION['error'] = 'Kullanıcı kaydedilemedi: ' . $dbErr;
            } else {
                $_SESSION['error'] = 'Kullanıcı kaydedilemedi. Kullanıcı adı veya e-posta zaten kullanılıyor olabilir.';
            }
        }

        header('Location: ' . esh_url('User', 'list'));
        exit;
    }

    public function delete() {
        AuthHelper::requireAdmin();
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('User', 'list'));
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }
        if (!AuthHelper::canManageUser($id)) {
            $_SESSION['error'] = 'Bu kullanıcıyı silme yetkiniz yok.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        $model = new User();
        if (!$model->load($id)) {
            $_SESSION['error'] = 'Kullanıcı bulunamadı.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        $level = AuthHelper::clampLevel((int) $model->isadmin);
        if ($level === AuthHelper::ROLE_PLATFORM_OWNER && AuthHelper::countPlatformOwners() <= 1) {
            $_SESSION['error'] = 'Sistemdeki son '
                . AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER)
                . ' silinemez.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }
        if ($level === AuthHelper::ROLE_SUPERADMIN && AuthHelper::countSuperadmins() <= 1) {
            $_SESSION['error'] = 'Sistemdeki son '
                . AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN)
                . ' silinemez.';
            header('Location: ' . esh_url('User', 'list'));
            exit;
        }

        if ($model->delete($id)) {
            \App\Helpers\AuditLogHelper::userDelete($model);
            $_SESSION['success'] = 'Kullanıcı silindi.';
        } else {
            $_SESSION['error'] = 'Kullanıcı silinemedi.';
        }

        header('Location: ' . esh_url('User', 'list'));
        exit;
    }
    
    public function image() {
        // Oturumdaki kullanıcı ID'sini al
        $userId = $_SESSION['user_id'];
        
        $userModel = new User();
        // BaseModel'den gelen load() metodu ile kullanıcıyı yükle
        $userModel->load($userId);
        
        // View dosyasında kullanmak üzere $user değişkenine ata
        $user = $userModel; 
        
        $temp_image = $_SESSION['temp_photo'] ?? null;
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'user/photo');
        include ThemeViewHelper::resolvePartial('footer');
    }
    

public function upload() {
    $this->cleanOldTempFiles(2);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
        $file = $_FILES['image'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Dosya yüklenemedi. Lütfen tekrar deneyin.';
            header('Location: ' . esh_url('User', 'image'));
            exit;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $_SESSION['error'] = 'Geçersiz yükleme isteği.';
            header('Location: ' . esh_url('User', 'image'));
            exit;
        }

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        if (!isset($allowedMimes[$mime])) {
            $_SESSION['error'] = 'Sadece JPG ve PNG dosyaları yüklenebilir.';
            header('Location: ' . esh_url('User', 'image'));
            exit;
        }
        $ext = $allowedMimes[$mime];

        $tempFolder = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'temp';
        $tempFileName = 'temp_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
        $tempPhysicalPath = $tempFolder . DIRECTORY_SEPARATOR . $tempFileName;

        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0755, true);
        }

        if (move_uploaded_file($tmp, $tempPhysicalPath)) {
            $_SESSION['temp_image'] = esh_upload_url('temp', $tempFileName);
            $_SESSION['temp_image_path'] = $tempPhysicalPath;
            header('Location: ' . esh_url('User', 'image'));
        } else {
            $_SESSION['error'] = "Dosya geçici dizine taşınamadı.";
            header('Location: ' . esh_url('User', 'index'));
        }
        exit;
    }
}

public function cropsave() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['temp_image'])) {
        if (!\extension_loaded('gd') || !\function_exists('imagecreatefromjpeg')) {
            $_SESSION['error'] = 'Profil fotoğrafı kırpma için PHP GD eklentisi gerekli. XAMPP: php\\php.ini içinde extension=gd satırının başındaki ; işaretini kaldırın ve Apache’yi yeniden başlatın.';
            header('Location: ' . esh_url('User', 'image'));
            exit;
        }

        $src = $_SESSION['temp_image_path'] ?? null;
        if (!is_string($src) || $src === '' || !is_file($src)) {
            $legacy = (string) ($_SESSION['temp_image'] ?? '');
            $src = $legacy !== '' && is_file($legacy) ? $legacy : (UPLOAD_PATH . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . basename($legacy));
        }
        if (!is_file($src)) {
            $_SESSION['error'] = 'Geçici profil fotoğrafı bulunamadı. Lütfen yeniden yükleyin.';
            header('Location: ' . esh_url('User', 'image'));
            exit;
        }
        $info = \getimagesize($src);
        $type = $info[2];

        // Kaynak resmi oluştur (GD: global namespace)
        $img = ($type === \IMAGETYPE_JPEG) ? \imagecreatefromjpeg($src) : \imagecreatefrompng($src);
        
        // Cropper.js'ten gelen koordinatlar (doğal piksel)
        $x = (int)$_POST['x'];
        $y = (int)$_POST['y'];
        $w = (int)$_POST['w'];
        $h = (int)$_POST['h'];

        // 300x300 boyutunda yeni bir boş resim oluştur
        $targ_w = 300;
        $targ_h = 300;
        $dest = \imagecreatetruecolor($targ_w, $targ_h);

        // PNG şeffaflık ayarı
        if ($type === \IMAGETYPE_PNG) {
            \imagealphablending($dest, false);
            \imagesavealpha($dest, true);
        }

        // Kesme ve Yeniden Boyutlandırma
        \imagecopyresampled($dest, $img, 0, 0, $x, $y, $targ_w, $targ_h, $w, $h);

        // Nihai klasöre kaydet
        // 1. Klasör yolunu ve dosya ismini belirle
        // UPLOAD_PATH: ROOT_PATH . '/public/uploads' idi. 
        // Biz bunun altına 'profile' klasörünü ekliyoruz.
        $profileFolder = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'profile';
        $finalName = 'user_' . $_SESSION['user_id'] . '_' . time() . '.jpg';
        $finalPath = $profileFolder . DIRECTORY_SEPARATOR . $finalName;

        // 2. Klasör var mı kontrol et, yoksa oluştur
        if (!is_dir($profileFolder)) {
            mkdir($profileFolder, 0755, true);
        }

        // 3. Resmi kaydet
        \imagejpeg($dest, $finalPath, 90);

        // 4. Veritabanına kaydedilecek yolu oluştur 
        // (Görünüm dosyalarında <img src="..."> içinde kullanabilmek için)
        $dbPath = '../public/uploads/profile/' . $finalName;

        // 5. Veritabanına kaydet, başarılıysa eski dosyayı sil
        $user = new User();
        $user->load($_SESSION['user_id']);
        $oldFileName = basename((string) ($user->image ?? ''));
        
        $user->set('image', $finalName);
        if ($user->store()) {
            User::syncSessionAvatar((string) AuthHelper::sessionUserId());
            $isDefault = in_array(strtolower($oldFileName), ['default.jpg', 'default.jpeg', 'default.png'], true);
            if ($oldFileName !== '' && !$isDefault && $oldFileName !== $finalName) {
                $oldFullPath = $profileFolder . DIRECTORY_SEPARATOR . $oldFileName;
                if (is_file($oldFullPath)) {
                    @unlink($oldFullPath);
                }
            }
        } else {
            @unlink($finalPath);
            $_SESSION['error'] = "Profil resmi kaydedilemedi.";
            header('Location: ' . esh_url('User', 'index'));
            exit;
        }

        // Temizlik: Geçici dosyayı ve session'ı sil
        // Geçici dosyayı session'dan al ve sil
        if (isset($_SESSION['temp_image']) || isset($_SESSION['temp_image_path'])) {
            $tempPhysicalPath = $_SESSION['temp_image_path'] ?? null;
            if (!is_string($tempPhysicalPath) || $tempPhysicalPath === '' || !is_file($tempPhysicalPath)) {
                $tempFileWebPath = (string) ($_SESSION['temp_image'] ?? '');
                $tempFileName = basename($tempFileWebPath);
                $tempPhysicalPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $tempFileName;
            }

            if (is_file($tempPhysicalPath)) {
                unlink($tempPhysicalPath);
            }
            unset($_SESSION['temp_image'], $_SESSION['temp_image_path']);
        }

        $_SESSION['success'] = "Profil resminiz başarıyla güncellendi.";
        header('Location: ' . esh_url('User', 'index'));
        exit;
    }
}

    /**
     * Oturumdaki kullanıcının yüklü profil fotoğrafını siler (dosya + veritabanı).
     */
    public function removephoto() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('User', 'index'));
            exit;
        }

        $userId = AuthHelper::sessionUserId();
        $user = new User();
        if ($userId === null || !$user->load($userId)) {
            $_SESSION['error'] = 'Kullanıcı bulunamadı.';
            header('Location: ' . esh_url('User', 'index'));
            exit;
        }

        $physicalPath = $user->profileImagePhysicalPath();
        if ($physicalPath === null) {
            $_SESSION['error'] = 'Silinecek profil fotoğrafı bulunamadı.';
            header('Location: ' . esh_url('User', 'index'));
            exit;
        }

        if (is_file($physicalPath)) {
            @unlink($physicalPath);
        }

        $user->set('image', null);
        if ($user->store()) {
            $_SESSION['success'] = 'Profil fotoğrafınız kaldırıldı.';
            User::syncSessionAvatar($userId);
        } else {
            $_SESSION['error'] = 'Profil fotoğrafı kaydı güncellenemedi.';
        }

        header('Location: ' . esh_url('User', 'index'));
        exit;
    }

    /**
     * Temp klasöründeki eski dosyaları temizler
     * @param int $hours Kaç saatten eski dosyalar silinsin?
     */
    private function cleanOldTempFiles($hours = 2) {
        $tempFolder = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'temp';
        
        if (!is_dir($tempFolder)) return;

        $files = glob($tempFolder . DIRECTORY_SEPARATOR . "*");
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                // Dosyanın son değiştirilme zamanı ile şu anki zamanı karşılaştır
                if ($now - filemtime($file) >= ($hours * 3600)) {
                    unlink($file);
                }
            }
        }
    }
}