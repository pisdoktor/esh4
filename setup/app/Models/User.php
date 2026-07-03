<?php
namespace App\Models;

use App\Helpers\AuthHelper;
use App\Helpers\FederationHelper;
use App\Helpers\TenantSqlHelper;
use App\Models\Kurum;

class User extends BaseModel {
    public const ROLE_STAFF = AuthHelper::ROLE_STAFF;
    public const ROLE_ADMIN = AuthHelper::ROLE_ADMIN;
    public const ROLE_SUPERADMIN = AuthHelper::ROLE_SUPERADMIN;
    public const ROLE_PLATFORM_OWNER = AuthHelper::ROLE_PLATFORM_OWNER;
    public $id = null;
    public $username = null;
    public $password = null;
    public $name = null;
    public $tckimlikno = null;
    public $email = null;
    public $image = null;
    public $nowvisit = null;
    public $lastvisit = null;
    public $registerDate = null;
    public $activated = 0;
    public $activation = null;
    public $isadmin = 0;
    /** @var int|null Kurum; platform hesapları için NULL */
    public $kurum_id = 1;
    /** @var int|null Süper yönetici federasyon bölge kapsamı (NULL = tüm bölgeler) */
    public $bolge_id = null;
    /** @var string|null kod: doktor, hemsire, tekniker, gerontolog, sekreter, eczaci, diger */
    public $unvan = null;
    /** @var string|null Kişisel tema slug; NULL = site varsayılanı (`ACTIVE_THEME`) */
    public $ui_theme = null;
    /** @var int 1 ise e-imza girişine izinli */
    public $eimza_enabled = 0;
    /** @var string|null Son e-imza girişinde kullanılan sertifika subject özeti */
    public $eimza_cert_subject = null;
    /** @var string|null Son e-imza girişinde kullanılan sertifika serial */
    public $eimza_cert_serial = null;
    /** @var string|null Son e-imza girişinde kullanılan sertifika SHA-256 fingerprint */
    public $eimza_cert_fingerprint = null;
    /** @var string|null Son e-imza giriş zamanı */
    public $eimza_last_login_at = null;

    public function __construct() {
        parent::__construct('#__users', 'id');
    }

    /**
     * Eski şemalarda olmayan sütunları (eimza_*, kurum_id vb.) INSERT/UPDATE dışında bırakır.
     */
    public function store($updateNulls = false) {
        $this->pruneDirtyToExistingColumns();

        return parent::store($updateNulls);
    }

    /** @var array<string, array<string, true>> */
    private static $tableColumnCache = [];

    private function pruneDirtyToExistingColumns(): void {
        $tbl = $this->_tbl;
        if (!isset(self::$tableColumnCache[$tbl])) {
            self::$tableColumnCache[$tbl] = [];
            $rows = $this->db->fetchObjectListPrepared(
                'SELECT COLUMN_NAME FROM information_schema.COLUMNS'
                . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$this->db->replacePrefix('#__users')]
            );
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $name = (string) ($row->COLUMN_NAME ?? '');
                    if ($name !== '') {
                        self::$tableColumnCache[$tbl][$name] = true;
                    }
                }
            }
        }
        foreach (array_keys($this->_dirty) as $field) {
            if (!isset(self::$tableColumnCache[$tbl][$field])) {
                unset($this->_dirty[$field]);
            }
        }
    }

    /**
     * Kullanıcıyı kullanıcı adına göre yükler (Login için kritik)
     */
    public function loadByUsername($username) {
        $data = $this->db->fetchOnePrepared('SELECT * FROM #__users WHERE username = ?', [$username]);
        if ($data) {
            $this->_dirty = [];
            $this->bind($data, false);
            return true;
        }
        return false;
    }

    /**
     * Kullanıcıyı TC kimlik numarasına göre yükler.
     */
    public function loadByTcKimlikNo(string $tcKimlikNo): bool
    {
        $tc = preg_replace('/\D+/', '', trim($tcKimlikNo));
        if ($tc === '' || strlen($tc) !== 11) {
            return false;
        }
        $data = $this->db->fetchOnePrepared('SELECT * FROM #__users WHERE tckimlikno = ? LIMIT 1', [$tc]);
        if (!$data) {
            return false;
        }
        $this->_dirty = [];
        $this->bind($data, false);

        return true;
    }

    /**
     * Parolayı doğrular.
     * - Yeni sistem: password_hash()/password_verify()
     * - Eski sistem: md5(password.salt):salt veya md5(password)
     * Başarılı legacy doğrulamada hash otomatik olarak password_hash formatına yükseltilir.
     */
    public function verifyPasswordAndUpgrade(string $plainPassword): bool
    {
        $stored = trim((string) ($this->password ?? ''));
        if ($stored === '') {
            return false;
        }

        // Yeni sistem hash doğrulama
        if (password_verify($plainPassword, $stored)) {
            if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                $this->set('password', password_hash($plainPassword, PASSWORD_DEFAULT));
                $this->store();
            }
            return true;
        }

        // Legacy: md5(password.salt):salt veya md5(password)
        $legacyOk = false;
        if (strpos($stored, ':') !== false) {
            [$crypt, $salt] = explode(':', $stored, 2);
            $crypt = trim((string) $crypt);
            $salt = trim((string) $salt);
            if ($crypt !== '' && $salt !== '') {
                $legacyOk = hash_equals($crypt, md5($plainPassword . $salt));
            }
        } else {
            $legacyOk = hash_equals($stored, md5($plainPassword));
        }

        if (!$legacyOk) {
            return false;
        }

        // Legacy başarılı: hash'i güncel formata yükselt
        $this->set('password', password_hash($plainPassword, PASSWORD_DEFAULT));
        $this->store();
        return true;
    }
    /**
     * Profil bilgilerini günceller
     */
    public function updateProfile($id, $data) {
        if ($this->load($id)) {
            $this->bind($data);
            return $this->store();
        }
        return false;
    }

    /**
     * Şifreyi güvenli bir şekilde hashleyerek günceller
     */
    public function updatePassword($id, $newPassword) {
        if ($this->load($id)) {
            $this->set('password', password_hash($newPassword, PASSWORD_DEFAULT));
            return $this->store();
        }
        return false;
    }

    /**
     * Kullanıcı fotoğraf yolunu günceller
     */
    public function updatePhoto($id, $path) {
        if ($this->load($id)) {
            $this->set('image', $path); // Özellik ismiyle uyumlu hale getirildi
            return $this->store();
        }
        return false;
    }

    /** Varsayılan profil fotoğrafı (public/uploads/profile/default.png). */
    public static function defaultProfileImageWebUrl(): string
    {
        return esh_upload_url('profile', 'default.png');
    }

    /** Liste/görünüm için ham DB değerinden güvenli profil fotoğraf URL'si üretir. */
    public static function profileImageWebUrlFromValue($image): string
    {
        $raw = trim((string) $image);
        if ($raw === '') {
            return self::defaultProfileImageWebUrl();
        }

        $base = basename($raw);
        if ($base === '' || in_array(strtolower($base), self::defaultProfileImageNames(), true)) {
            return self::defaultProfileImageWebUrl();
        }

        return esh_upload_url('profile', $base);
    }

    /** @return list<string> */
    public static function defaultProfileImageNames(): array
    {
        return ['default.jpg', 'default.jpeg', 'default.png'];
    }

    public function isDefaultProfileImageName(?string $fileName): bool
    {
        $base = basename(trim((string) $fileName));
        if ($base === '') {
            return true;
        }

        return in_array(strtolower($base), self::defaultProfileImageNames(), true);
    }

    /** Yüklü profil dosyası diskte varsa tam yol; aksi halde null. */
    public function profileImagePhysicalPath(): ?string
    {
        $fileName = basename((string) ($this->image ?? ''));
        if ($this->isDefaultProfileImageName($fileName)) {
            return null;
        }
        $path = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($path)) {
            return null;
        }
        $realProfile = realpath(UPLOAD_PATH . DIRECTORY_SEPARATOR . 'profile');
        $realFile = realpath($path);
        if ($realProfile === false || $realFile === false || strpos($realFile, $realProfile) !== 0) {
            return null;
        }

        return $path;
    }

    public function profileImageWebUrl(): string
    {
        $path = $this->profileImagePhysicalPath();
        if ($path !== null) {
            return esh_upload_url('profile', basename($path));
        }

        return self::defaultProfileImageWebUrl();
    }

    /** Oturumdaki üst menü avatar URL'sini günceller. */
    public static function syncSessionAvatar(int $userId): void
    {
        $user = new self();
        if ($userId > 0 && $user->load($userId)) {
            $_SESSION['avatar'] = $user->profileImageWebUrl();
        } else {
            $_SESSION['avatar'] = self::defaultProfileImageWebUrl();
        }
    }

    public function hasRemovableProfilePhoto(): bool
    {
        return $this->profileImagePhysicalPath() !== null;
    }

    /**
     * Kullanıcı giriş yaptığında ziyaret tarihlerini günceller
     */
    public function updateVisitDate($id) {
        if ($this->load($id)) {
            $this->set('lastvisit', $this->nowvisit);
            $this->set('nowvisit', date('Y-m-d H:i:s'));
            return $this->store();
        }
        return false;
    }
    
    /**
     * E-posta adresine göre kullanıcıyı bulur
     */
    public function getByEmail($email) {
        return $this->db->fetchObjectPrepared('SELECT * FROM #__users WHERE email = ?', [$email]);
    }

    /**
     * Şifre sıfırlama kodu oluşturur ve kullanıcıya atar
     */
    public function createResetToken($id) {
        if ($this->load($id)) {
            // Güvenli, rastgele 12 haneli bir kod üretir
            $token = substr(bin2hex(random_bytes(6)), 0, 12);
            $this->set('activation', $token);
            return $this->store() ? $token : false;
        }
        return false;
    }

    /**
     * Token ile kullanıcıyı doğrular ve şifreyi günceller
     */
    public function resetPasswordWithToken($token, $newPassword) {
        $userId = $this->db->loadResultPrepared('SELECT id FROM #__users WHERE activation = ?', [$token]);

        if ($userId) {
            $this->load($userId);
            $this->set('password', password_hash($newPassword, PASSWORD_DEFAULT));
            $this->set('activation', null);
            return $this->store();
        }
        return false;
    }
    
    /**
     * Kullanıcı (personel) listesi.
     *
     * @param bool $onlyActivated true ise yalnızca `activated = 1` (izlem/plan personel seçimi vb.);
     *                             false ise tüm kayıtlar (admin «Kullanıcı yönetimi» listesi).
     */
    public function getList(bool $onlyActivated = true) {
        $parts = [];
        if ($onlyActivated) {
            $parts[] = 'activated = 1';
        }
        TenantSqlHelper::mergeParts($parts, '', 'kurum_id');
        $where = $parts !== [] ? ' WHERE ' . implode(' AND ', $parts) : '';
        $query = 'SELECT * FROM #__users' . $where . ' ORDER BY name ASC';

        return $this->db->fetchObjectListPrepared($query);
    }

    /**
     * Admin «Kullanıcı yönetimi» listesi — isteğe bağlı activated / isadmin / unvan süzgeci.
     *
     * @param int|null $activatedFilter 1, 0 veya null (hepsi)
     * @param int|null $isadminFilter   0 personel, 1 yönetici+ (isadmin>=1), 2 süper yönetici, 3 sistem sahibi, null hepsi
     * @param bool     $unvanEmptyOnly true ise yalnızca ünvanı boş (NULL veya trim sonrası '') kayıtlar
     * @param string|null $unvanCode normalize edilmiş ünvan kodu (doktor, hemsire, …) veya null
     * @param int|null    $kurumIdFilter süper yönetici liste filtresi (kurum_id); null ise oturum/navbar kapsamı
     * @param int|null    $bolgeIdFilter sistem sahibi bölge filtresi; kurum seçiliyken yok sayılır
     * @return array<int, object>
     */
    public function getAdminList(
        ?int $activatedFilter = null,
        ?int $isadminFilter = null,
        bool $unvanEmptyOnly = false,
        ?string $unvanCode = null,
        ?int $kurumIdFilter = null,
        ?int $bolgeIdFilter = null,
        string $orderFragment = 'u.name ASC'
    ): array {
        $parts = [];
        $params = [];
        if ($activatedFilter === 0 || $activatedFilter === 1) {
            $parts[] = 'u.activated = ' . $activatedFilter;
        }
        if ($isadminFilter === 0) {
            $parts[] = 'u.isadmin = 0';
        } elseif ($isadminFilter === 1) {
            $parts[] = 'u.isadmin >= 1';
        } elseif ($isadminFilter === 2) {
            $parts[] = 'u.isadmin = 2';
        } elseif ($isadminFilter === 3) {
            $parts[] = 'u.isadmin = 3';
        }
        if (!AuthHelper::sessionIsPlatformOwner()) {
            $parts[] = 'u.isadmin <= ' . AuthHelper::ROLE_SUPERADMIN;
        }
        if ($unvanEmptyOnly) {
            $parts[] = '(u.unvan IS NULL OR TRIM(u.unvan) = \'\')';
        } elseif ($unvanCode !== null && $unvanCode !== '') {
            $parts[] = 'u.unvan = ?';
            $params[] = $unvanCode;
        }
        if ($kurumIdFilter !== null && $kurumIdFilter > 0) {
            $parts[] = 'u.kurum_id = ' . (int) $kurumIdFilter;
        } elseif ($bolgeIdFilter !== null && $bolgeIdFilter > 0) {
            $bolgeId = (int) $bolgeIdFilter;
            $orParts = [];
            if (FederationHelper::columnsReady()) {
                $kurumIds = FederationHelper::activeKurumIdsForBolge($bolgeId);
                if ($kurumIds !== []) {
                    $orParts[] = 'u.kurum_id IN (' . implode(',', array_map('intval', $kurumIds)) . ')';
                }
            }
            $orParts[] = '(u.isadmin = ' . AuthHelper::ROLE_SUPERADMIN . ' AND u.bolge_id = ' . $bolgeId . ')';
            if (AuthHelper::sessionIsPlatformOwner()) {
                $orParts[] = 'u.isadmin = ' . AuthHelper::ROLE_PLATFORM_OWNER;
            }
            $parts[] = '(' . implode(' OR ', $orParts) . ')';
        } else {
            TenantSqlHelper::mergeParts($parts, 'u', 'kurum_id');
        }
        $where = $parts !== [] ? 'WHERE ' . implode(' AND ', $parts) : '';
        $query = 'SELECT u.*, k.kod AS kurum_slug, k.ad AS kurum_adi FROM #__users u'
            . ' LEFT JOIN #__kurumlar k ON k.id = u.kurum_id'
            . ($where !== '' ? ' ' . $where : '')
            . ' ORDER BY ' . (trim($orderFragment) !== '' ? $orderFragment : 'u.name ASC');

        $list = $this->db->fetchObjectListPrepared($query, $params);

        return is_array($list) ? $list : [];
    }

    /**
     * Mesajlaşma alıcı listesi — kurum filtresi olmadan tüm aktif admin/süper yönetici.
     *
     * @return array<int, object>
     */
    public function getMessagingAdminList(): array
    {
        $query = 'SELECT u.*, k.kod AS kurum_slug, k.ad AS kurum_adi FROM #__users u'
            . ' LEFT JOIN #__kurumlar k ON k.id = u.kurum_id'
            . ' WHERE u.activated = 1 AND u.isadmin >= 1'
            . ' ORDER BY u.name ASC';
        $list = $this->db->fetchObjectListPrepared($query);

        return is_array($list) ? $list : [];
    }

    public function getUserNames($user_ids) {
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $user_ids))));
        if ($ids === []) {
            return [];
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);

        return $this->db->fetchColumnListPrepared("SELECT name FROM #__users WHERE id IN ($inSql)", $inParams);
    }

    /**
     * Ekip planlamasında yetkinlik eşlemesi için ünvan kodları.
     *
     * @return list<string>
     */
    public function getUserUnvans($user_ids) {
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $user_ids))));
        if ($ids === []) {
            return [];
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);

        return $this->db->fetchColumnListPrepared("SELECT unvan FROM #__users WHERE id IN ($inSql)", $inParams);
    }

    /**
     * Profil ekranı için kullanıcı bazlı izlem/plan ve personel modülü özetleri.
     *
     * @return array<string, int|string|null>
     */
    public function getProfileStats(int $userId): array
    {
        $uid = (int) $userId;
        if ($uid <= 0) {
            return [];
        }

        $inIzlem = "FIND_IN_SET(" . $uid . ", REPLACE(CAST(i.izlemiyapan AS CHAR), ' ', ''))";
        $inIzlemI2 = "FIND_IN_SET(" . $uid . ", REPLACE(CAST(i2.izlemiyapan AS CHAR), ' ', ''))";
        $inPlan = "FIND_IN_SET(" . $uid . ", REPLACE(CAST(p.planiyapan AS CHAR), ' ', ''))";
        $inPlanP2 = "FIND_IN_SET(" . $uid . ", REPLACE(CAST(p2.planiyapan AS CHAR), ' ', ''))";

        $stats = [];
        $stats['visits_total'] = (int) $this->db->loadResultPrepared(
            "SELECT COUNT(*) FROM #__izlemler i WHERE {$inIzlem}"
        );
        $stats['visits_done'] = (int) $this->db->loadResultPrepared(
            "SELECT COUNT(*) FROM #__izlemler i WHERE {$inIzlem} AND i.yapildimi = 1"
        );
        $stats['visits_missed'] = (int) $this->db->loadResultPrepared(
            "SELECT COUNT(*) FROM #__izlemler i WHERE {$inIzlem} AND i.yapildimi = 0"
        );

        $stats['plans_total'] = (int) $this->db->loadResultPrepared(
            "SELECT COUNT(*) FROM #__pizlemler p WHERE {$inPlan}"
        );
        $stats['plans_open'] = (int) $this->db->loadResultPrepared(
            "SELECT COUNT(*) FROM #__pizlemler p WHERE {$inPlan} AND p.durum = 0"
        );
        $stats['plans_done'] = (int) $this->db->loadResultPrepared(
            "SELECT COUNT(*) FROM #__pizlemler p WHERE {$inPlan} AND p.durum = 1"
        );

        $stats['visits_this_month'] = (int) $this->db->loadResultPrepared(
            "SELECT COUNT(*) FROM #__izlemler i
             WHERE {$inIzlem}
               AND DATE_FORMAT(i.izlemtarihi, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
        );
        $stats['plans_this_month'] = (int) $this->db->loadResultPrepared(
            "SELECT COUNT(*) FROM #__pizlemler p
             WHERE {$inPlan}
               AND DATE_FORMAT(p.planlanantarih, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
        );

        $stats['last_visit_action'] = $this->db->loadResultPrepared(
            "SELECT MAX(i.izlemtarihi) FROM #__izlemler i WHERE {$inIzlem}"
        );
        $stats['last_plan_action'] = $this->db->loadResultPrepared(
            "SELECT MAX(p.plantarihi) FROM #__pizlemler p WHERE {$inPlan}"
        );

        $stats['visit_completion_pct'] = $stats['visits_total'] > 0
            ? (int) round(100.0 * $stats['visits_done'] / $stats['visits_total'])
            : null;

        $visitExtra = $this->db->fetchOnePrepared(
            "SELECT
                (SELECT COUNT(DISTINCT i2.hastatckimlik) FROM #__izlemler i2 WHERE {$inIzlemI2}) AS visits_distinct_patients,
                (SELECT COUNT(*) FROM #__izlemler i2 WHERE {$inIzlemI2} AND YEAR(i2.izlemtarihi) = YEAR(CURDATE())) AS visits_this_year,
                (SELECT COUNT(*) FROM #__izlemler i2 WHERE {$inIzlemI2} AND i2.izlemtarihi >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS visits_last_30_days,
                (SELECT COUNT(*) FROM #__izlemler i2 WHERE {$inIzlemI2} AND i2.izlemtarihi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) AS visits_last_7_days,
                (SELECT COUNT(*) FROM #__izlemler i2 WHERE {$inIzlemI2} AND COALESCE(i2.arac, 0) > 0) AS visits_with_vehicle,
                (SELECT COUNT(*) FROM #__izlemler i2 WHERE {$inIzlemI2} AND TRIM(COALESCE(i2.brans, '')) <> '') AS visits_with_kons,
                (SELECT MIN(i2.izlemtarihi) FROM #__izlemler i2 WHERE {$inIzlemI2}) AS first_visit_date"
        );
        if (is_array($visitExtra)) {
            foreach ($visitExtra as $k => $v) {
                if ($k === 'first_visit_date') {
                    $stats[$k] = $v !== null && $v !== '' ? (string) $v : null;
                } else {
                    $stats[$k] = (int) $v;
                }
            }
        }

        $planExtra = $this->db->fetchOnePrepared(
            "SELECT
                (SELECT COUNT(DISTINCT p2.hastatckimlik) FROM #__pizlemler p2 WHERE {$inPlanP2}) AS plans_distinct_patients,
                (SELECT COUNT(*) FROM #__pizlemler p2 WHERE {$inPlanP2} AND YEAR(p2.planlanantarih) = YEAR(CURDATE())) AS plans_this_year,
                (SELECT COUNT(*) FROM #__pizlemler p2 WHERE {$inPlanP2} AND p2.durum = 0 AND p2.planlanantarih < CURDATE()) AS plans_open_overdue,
                (SELECT COUNT(*) FROM #__pizlemler p2 WHERE {$inPlanP2} AND p2.durum = 0
                    AND p2.planlanantarih >= CURDATE()
                    AND p2.planlanantarih <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)) AS plans_due_next_7_days"
        );
        if (is_array($planExtra)) {
            foreach ($planExtra as $k => $v) {
                $stats[$k] = (int) $v;
            }
        }

        $personnelExtra = $this->db->fetchOnePrepared(
            "SELECT
                (SELECT COUNT(*) FROM #__personel_nobet n WHERE n.personel_id = ? AND n.durum = 1) AS nobet_total,
                (SELECT COUNT(*) FROM #__personel_nobet n WHERE n.personel_id = ? AND n.durum = 1
                    AND DATE_FORMAT(n.nobet_tarihi, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')) AS nobet_this_month,
                (SELECT COUNT(*) FROM #__personel_izin iz WHERE iz.personel_id = ?) AS izin_total,
                (SELECT COUNT(*) FROM #__personel_istek ist WHERE ist.personel_id = ?) AS istek_total,
                (SELECT COUNT(*) FROM #__ekipler e
                    WHERE FIND_IN_SET(?, REPLACE(COALESCE(e.user_ids, ''), ' ', ''))) AS ekip_assignments,
                (SELECT COUNT(*) FROM #__hasta_yara_fotolar wf WHERE wf.yukleyen_id = ?) AS wound_photos_uploaded",
            [$uid, $uid, $uid, $uid, $uid, $uid]
        );
        if (is_array($personnelExtra)) {
            foreach ($personnelExtra as $k => $v) {
                $stats[$k] = (int) $v;
            }
        }

        $stats['ilacrapor_rows_for_tc'] = 0;
        $tcRow = $this->db->fetchOnePrepared('SELECT tckimlikno FROM #__users WHERE id = ?', [$uid]);
        $tcRaw = is_array($tcRow) ? trim((string) ($tcRow['tckimlikno'] ?? '')) : '';
        if ($tcRaw !== '' && preg_match('/^\d{11}$/', $tcRaw)) {
            $stats['ilacrapor_rows_for_tc'] = (int) $this->db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__hastailacrapor WHERE hastatckimlik = ?',
                [$tcRaw]
            );
        }

        return $stats;
    }

    /**
     * Admin personel ünvanı: değer => görünen etiket (boş = seçilmedi).
     *
     * @return array<string, string>
     */
    public static function unvanChoices(): array
    {
        $choices = ['' => 'Seçiniz…'] + Unvan::getActiveChoicesMap();

        return $choices;
    }

    /** @var array<int, string> */
    private static $kurumLabelCache = [];

    /**
     * Profil ve listelerde kurum adı (kod parantez içinde).
     */
    public static function kurumDisplayLabel(object $user): string
    {
        if ((int) ($user->isadmin ?? 0) === AuthHelper::ROLE_PLATFORM_OWNER) {
            return 'Platform (sistem sahibi)';
        }
        if ((int) ($user->isadmin ?? 0) === AuthHelper::ROLE_SUPERADMIN) {
            $bid = isset($user->bolge_id) && $user->bolge_id !== null ? (int) $user->bolge_id : 0;
            if ($bid > 0 && class_exists(\App\Helpers\FederationHelper::class)) {
                $bolgeLabel = \App\Helpers\FederationHelper::kurumBolgeLabel($bid);

                return 'Platform (süper yönetici — ' . $bolgeLabel . ')';
            }

            return 'Platform (süper yönetici)';
        }

        $kid = isset($user->kurum_id) && $user->kurum_id !== null ? (int) $user->kurum_id : 0;
        if ($kid <= 0) {
            return '—';
        }

        if (isset(self::$kurumLabelCache[$kid])) {
            return self::$kurumLabelCache[$kid];
        }

        $kurum = new Kurum();
        if (!$kurum->load($kid)) {
            self::$kurumLabelCache[$kid] = '—';

            return '—';
        }

        $ad = trim((string) ($kurum->ad ?? ''));
        $kod = trim((string) ($kurum->kod ?? ''));
        if ($ad !== '' && $kod !== '') {
            $label = $ad . ' (' . $kod . ')';
        } elseif ($ad !== '') {
            $label = $ad;
        } elseif ($kod !== '') {
            $label = $kod;
        } else {
            $label = '—';
        }

        self::$kurumLabelCache[$kid] = $label;

        return $label;
    }

    public static function unvanLabel(?string $code): string
    {
        $code = $code === null ? '' : trim($code);
        if ($code === '') {
            return '—';
        }

        return Unvan::labelForKod($code);
    }

    /**
     * POST’tan gelen ünvanı güvenli kod veya null yapar.
     */
    public static function normalizeUnvan($value): ?string
    {
        $code = Unvan::normalizeKod(is_string($value) ? $value : '');
        if ($code === null) {
            return null;
        }

        return Unvan::isValidActiveKod($code) ? $code : null;
    }

    /**
     * Nöbet «İzin/Mazeret» (Nobet::mine): OperationalSettings::nobetAllowedUnvanlar() içindeki ünvan.
     * Oturumdaki kullanıcı için istek başına bir kez DB okur (static önbellek).
     */
    public static function canAccessNobetMine(): bool
    {
        static $resolved = false;
        static $allowed = false;
        if ($resolved) {
            return $allowed;
        }
        $resolved = true;
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user_id'])) {
            return false;
        }
        $u = new self();
        $uid = (int) $_SESSION['user_id'];
        if ($uid <= 0 || !$u->load($uid)) {
            return false;
        }
        $code = trim((string) ($u->unvan ?? ''));
        $allowed = $code !== '' && in_array($code, \App\Helpers\OperationalSettings::nobetAllowedUnvanlar(), true);

        return $allowed;
    }
}