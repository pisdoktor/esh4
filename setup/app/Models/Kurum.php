<?php



declare(strict_types=1);



namespace App\Models;



use App\Core\Database;



/**

 * Kurum (tenant) modeli — #__kurumlar.

 */

class Kurum extends BaseModel

{

    public $id = null;

    public $ad = null;

    public $kod = null;

    public $aktif = 1;
    /** @var int|null Federasyon bölgesi */
    public $bolge_id = null;
    /** @var string|null Uzak düğüm kurum referansı */
    public $federation_ref = null;
    public $logo = null;

    public $adres = null;

    public $telefon = null;

    public $ayarlar_json = null;

    public $olusturma_tarihi = null;



    public function __construct()

    {

        parent::__construct('#__kurumlar', 'id');

    }



    /** @return list<object> */

    public function getList(bool $onlyActive = false, string $orderFragment = 'ad ASC', ?int $bolgeId = null): array

    {

        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'ad ASC';
        $parts = [];
        $params = [];
        if ($onlyActive) {

            $parts[] = 'aktif = 1';

        }
        if ($bolgeId !== null && $bolgeId > 0) {

            $parts[] = 'bolge_id = ?';
            $params[] = $bolgeId;

        }
        $sql = 'SELECT * FROM #__kurumlar';
        if ($parts !== []) {

            $sql .= ' WHERE ' . implode(' AND ', $parts);

        }

        $sql .= ' ORDER BY ' . $orderFragment;

        $list = $this->db->fetchObjectListPrepared($sql, $params);



        return is_array($list) ? $list : [];

    }



    public function loadByKod(string $kod): bool

    {

        $kod = trim(strtolower($kod));

        if ($kod === '') {

            return false;

        }

        $row = $this->db->fetchOnePrepared(

            'SELECT * FROM #__kurumlar WHERE kod = ? LIMIT 1',

            [$kod]

        );

        if (!$row) {

            return false;

        }

        $this->_dirty = [];

        $this->bind($row, false);



        return true;

    }



    public static function normalizeKod(string $raw): string

    {

        $s = strtolower(trim($raw));

        $s = preg_replace('/[^a-z0-9\-_]+/', '-', $s) ?? '';

        $s = trim($s, '-');



        return $s !== '' ? $s : 'kurum';

    }



    /** @return array<string, mixed> */

    public function ayarlarArray(): array

    {

        $raw = trim((string) ($this->ayarlar_json ?? ''));

        if ($raw === '') {

            return [];

        }

        $decoded = json_decode($raw, true);



        return is_array($decoded) ? $decoded : [];

    }



    public function setAyarlarArray(array $data): void

    {

        $this->set('ayarlar_json', json_encode($data, JSON_UNESCAPED_UNICODE));

    }



    public function getAyar(string $key, mixed $default = null): mixed

    {

        $arr = $this->ayarlarArray();



        return array_key_exists($key, $arr) ? $arr[$key] : $default;

    }



    public function kodUnique(string $kod, ?int $excludeId = null): bool

    {

        $kod = self::normalizeKod($kod);

        $params = [$kod];

        $sql = 'SELECT COUNT(*) FROM #__kurumlar WHERE kod = ?';

        if ($excludeId !== null && $excludeId > 0) {

            $sql .= ' AND id <> ?';

            $params[] = $excludeId;

        }



        return (int) $this->db->loadResultPrepared($sql, $params) === 0;

    }



    public static function tableExists(): bool

    {

        try {

            $db = Database::getInstance();



            return (int) $db->loadResultPrepared(

                'SELECT COUNT(*) FROM information_schema.TABLES

                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',

                [$db->replacePrefix('#__kurumlar')]

            ) > 0;

        } catch (\Throwable) {

            return false;

        }

    }

}

