<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Personel ünvanı kataloğu (#__unvanlar).
 */
class Unvan extends BaseModel
{
    public $id = null;
    public $kod = null;
    public $ad = null;
    public $kategori = 'diger';
    public $izin_sablonu = 'personel';
    public $sort_order = 100;
    public $aktif = 1;
    public $is_system = 0;
    public $mevzuat_notu = null;

    /** @var array<string, string>|null */
    private static $activeChoicesCache = null;

    /** @var array<string, true>|null */
    private static $validKodCache = null;

    public function __construct()
    {
        parent::__construct('#__unvanlar', 'id');
    }

    public static function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        try {
            $db = Database::getInstance();
            $tbl = $db->replacePrefix('#__unvanlar');
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
                [$tbl]
            );
            $exists = $row !== null && $row !== false && $row !== '';
        } catch (\Throwable $e) {
            $exists = false;
        }

        return $exists;
    }

    /**
     * @return array<string, string> kod => ad (aktif ünvanlar, sıralı)
     */
    public static function getActiveChoicesMap(): array
    {
        if (!self::tableExists()) {
            return self::legacyChoicesMap();
        }
        if (self::$activeChoicesCache !== null) {
            return self::$activeChoicesCache;
        }
        $db = Database::getInstance();
        $rows = $db->fetchObjectListPrepared(
            'SELECT kod, ad FROM #__unvanlar WHERE aktif = 1 ORDER BY sort_order, ad'
        );
        $out = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $kod = trim((string) ($row->kod ?? ''));
                if ($kod === '') {
                    continue;
                }
                $out[$kod] = (string) ($row->ad ?? $kod);
            }
        }
        if ($out === []) {
            $out = self::legacyChoicesMap();
        }
        self::$activeChoicesCache = $out;

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function legacyChoicesMap(): array
    {
        return [
            'doktor' => 'Doktor',
            'hemsire' => 'Hemşire',
            'tekniker' => 'Tekniker',
            'gerontolog' => 'Gerontolog',
            'sekreter' => 'Sekreter',
            'tibbi_sekreter' => 'Tıbbi Sekreter',
            'eczaci' => 'Eczacı',
            'diger' => 'Diğer',
        ];
    }

    public static function clearChoicesCache(): void
    {
        self::$activeChoicesCache = null;
        self::$validKodCache = null;
    }

    /**
     * @return list<string>
     */
    public static function kategoriChoices(): array
    {
        return [
            'hekim' => 'Hekim',
            'hemsirelik' => 'Hemşirelik / sağlık personeli',
            'teknik' => 'Teknik personel',
            'multidisipliner' => 'Multidisipliner',
            'idari' => 'İdari / destek',
            'diger' => 'Diğer',
        ];
    }

    /**
     * @return list<string>
     */
    public static function izinSablonuChoices(): array
    {
        return [
            'personel' => 'Personel (varsayılan)',
            'doktor' => 'Doktor',
            'hemsire' => 'Hemşire',
            'tekniker' => 'Tekniker',
            'eczaci' => 'Eczacı',
        ];
    }

    /**
     * @return list<object>
     */
    public function getList(string $orderFragment = 'sort_order ASC, ad ASC', bool $onlyActive = false): array
    {
        if (!self::tableExists()) {
            return [];
        }
        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'sort_order ASC, ad ASC';
        $sql = 'SELECT * FROM #__unvanlar';
        if ($onlyActive) {
            $sql .= ' WHERE aktif = 1';
        }
        $sql .= ' ORDER BY ' . $orderFragment;
        $list = $this->db->fetchObjectListPrepared($sql);

        return is_array($list) ? $list : [];
    }

    public function loadByKod(string $kod): bool
    {
        $kod = self::normalizeKod($kod);
        if ($kod === null || !self::tableExists()) {
            return false;
        }
        $row = $this->db->fetchObjectPrepared(
            'SELECT * FROM #__unvanlar WHERE kod = ? LIMIT 1',
            [$kod]
        );
        if ($row === null) {
            return false;
        }
        $this->bind($row, false);

        return true;
    }

    public static function kodExists(string $kod, ?int $excludeId = null): bool
    {
        $kod = self::normalizeKod($kod);
        if ($kod === null || !self::tableExists()) {
            return false;
        }
        $db = Database::getInstance();
        $sql = 'SELECT 1 FROM #__unvanlar WHERE kod = ?';
        $params = [$kod];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $row = $db->loadResultPrepared($sql, $params);

        return $row !== null && $row !== false && $row !== '';
    }

    public static function labelForKod(?string $kod): string
    {
        $code = is_string($kod) ? trim($kod) : '';
        if ($code === '') {
            return '';
        }
        $map = self::getActiveChoicesMap();
        if (isset($map[$code])) {
            return $map[$code];
        }
        if (self::tableExists()) {
            $db = Database::getInstance();
            $ad = $db->loadResultPrepared(
                'SELECT ad FROM #__unvanlar WHERE kod = ? LIMIT 1',
                [$code]
            );
            if (is_string($ad) && $ad !== '') {
                return $ad;
            }
        }
        $legacy = self::legacyChoicesMap();

        return $legacy[$code] ?? $code;
    }

    public static function normalizeKod($value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        $v = strtolower(trim((string) $value));
        if ($v === '') {
            return null;
        }
        if (!preg_match('/^[a-z][a-z0-9_]{0,62}$/', $v)) {
            return null;
        }

        return $v;
    }

    public static function isValidActiveKod(?string $kod): bool
    {
        $code = self::normalizeKod($kod ?? '');
        if ($code === null) {
            return false;
        }
        if (!self::tableExists()) {
            return array_key_exists($code, self::legacyChoicesMap());
        }
        if (self::$validKodCache === null) {
            self::$validKodCache = [];
            foreach (array_keys(self::getActiveChoicesMap()) as $k) {
                self::$validKodCache[$k] = true;
            }
        }

        return isset(self::$validKodCache[$code]);
    }

    public function countUsersWithKod(string $kod): int
    {
        $kod = self::normalizeKod($kod);
        if ($kod === null) {
            return 0;
        }

        return (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__users WHERE unvan = ?',
            [$kod]
        );
    }
}
