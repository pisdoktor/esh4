<?php
namespace App\Models;

use App\Core\Database;
use App\Helpers\KurumAdresScope;
use App\Helpers\StatsQueryCache;
use App\Helpers\TenantSqlHelper;
use App\Helpers\TomtomGeocodeQuotaHelper;
use PDO;

/**
 * Adres Tablosu Modeli — ilçe/mahalle/sokak/kapı platform geneli (tüm kurumlar ortak).
 * Kurum bazlı mahalle planı (bölge/gün): #__mahalle_plan + MahallePlan modeli.
 */
class Address extends BaseModel {

    private ?object $kapinoCoordStatsCache = null;
    
    public $id = null;
    public $adi = null;
    public $ust_id = null;
    public $tip = null;
    public $coords = null;
    /** kapino: 1 = coords dolu (indeksli sayım için) */
    public $has_coords = 0;

    public function __construct() {
        // #__adrestablosu tablosu, anahtar sütunu 'id'
        parent::__construct('#__adrestablosu', 'id');
    }

    /**
     * Tüm ilçeleri getirir
     */
    public function getDistricts() {
        $kid = KurumAdresScope::effectiveKurumId();
        $query = "SELECT a.id, a.adi FROM {$this->_tbl} AS a WHERE a.tip = 'ilce'";
        if ($kid !== null) {
            $query .= KurumAdresScope::sqlFilterForTip('ilce', 'a', $kid);
        }
        $query .= ' ORDER BY a.adi ASC';

        return $this->db->fetchObjectListPrepared($query);
    }

    /**
     * Adres hasta filtresi — ilçeler (aktif hasta sayısı ile).
     *
     * @return list<object>
     */
    public function getDistrictsWithActivePatientCounts(): array {
        $kid = KurumAdresScope::effectiveKurumId();
        $sql = 'SELECT i.id, i.adi, COUNT(h.id) AS sayi
            FROM ' . $this->_tbl . ' AS i
            LEFT JOIN #__hastalar AS h ON h.ilce = i.id AND h.pasif = ?' . TenantSqlHelper::andEquals('h') . '
            WHERE i.tip = ?';
        if ($kid !== null) {
            $sql .= KurumAdresScope::sqlFilterForTip('ilce', 'i', $kid);
        }
        $sql .= '
            GROUP BY i.id, i.adi
            ORDER BY i.adi ASC';
        $list = $this->db->fetchObjectListPrepared($sql, ['0', 'ilce']);

        return is_array($list) ? $list : [];
    }

    /**
     * Adres hasta filtresi — mahalle / sokak / kapı (üst kayıt + aktif hasta sayısı).
     *
     * @return list<object>
     */
    public function getAdresFilterChildrenWithCounts(string $parentId, string $tip): array {
        $allowed = ['mahalle' => 'mahalle', 'sokak' => 'sokak', 'kapino' => 'kapino'];
        if (!isset($allowed[$tip])) {
            return [];
        }
        $parentId = trim($parentId);
        if ($parentId === '') {
            return [];
        }
        $hCol = $allowed[$tip];
        $kid = KurumAdresScope::effectiveKurumId();
        $sql = 'SELECT i.id, i.adi, COUNT(h.id) AS sayi
            FROM ' . $this->_tbl . ' AS i
            LEFT JOIN #__hastalar AS h ON h.' . $hCol . ' = i.id AND h.pasif = ?' . TenantSqlHelper::andEquals('h') . '
            WHERE i.tip = ?
            AND i.ust_id = ?';
        if ($kid !== null && in_array($tip, ['mahalle', 'sokak', 'kapino'], true)) {
            $sql .= KurumAdresScope::sqlFilterForTip($tip, 'i', $kid, $parentId);
        }
        $sql .= '
            GROUP BY i.id, i.adi
            ORDER BY i.adi ASC';
        $list = $this->db->fetchObjectListPrepared($sql, ['0', $tip, $parentId]);
        if (!is_array($list)) {
            return [];
        }
        if ($tip === 'kapino') {
            return array_values(self::sortObjectListByAdi($list, 'kapino'));
        }

        return $list;
    }

    /**
     * Alt birimleri getirir (Mahalle, Sokak vb.)
     */
    public function getSubs($parentId, $type) {
        $kid = KurumAdresScope::effectiveKurumId();
        $query = "SELECT a.id, a.adi FROM {$this->_tbl} AS a
              WHERE a.ust_id = ?
              AND a.tip = ?";
        $subsParams = [(string) $parentId, (string) $type];
        if ($kid !== null && in_array((string) $type, ['mahalle', 'sokak', 'kapino'], true)) {
            $query .= KurumAdresScope::sqlFilterForTip((string) $type, 'a', $kid, (string) $parentId);
        }
        $query .= ' ORDER BY a.adi ASC';

        $result = $this->db->fetchObjectListPrepared($query, $subsParams);
        if (is_array($result) && (string) $type === 'kapino') {
            $result = array_values(self::sortObjectListByAdi($result, 'kapino'));
        }

        // Eğer veritabanında yoksa dış servisten çek (ilçe altı mahalle, mahalle altı sokak, sokak altı kapı)
        if (empty($result) && in_array($type, ['mahalle', 'sokak', 'kapino'], true)) {
            return $this->fetchFromExternalService($parentId, $type);
        }

        return $result;
    }

    /**
     * Sokak altında aynı ada sahip kapı kaydı var mı (tam eşleşme).
     */
    public function findKapinoIdByAdiUnderSokak(string $sokakId, string $adi): ?string
    {
        $sokakId = trim($sokakId);
        $adi = trim($adi);
        if ($sokakId === '' || $adi === '') {
            return null;
        }
        $query = 'SELECT id FROM ' . $this->_tbl
            . ' WHERE ust_id = ? AND tip = ? AND adi = ? LIMIT 1';
        $row = $this->db->fetchObjectPrepared($query, [$sokakId, 'kapino', $adi]);
        if (!$row || empty($row->id)) {
            return null;
        }

        return (string) $row->id;
    }

    /**
     * Mahalle altında aynı ada sahip sokak kaydı var mı (tam eşleşme).
     */
    public function findSokakIdByAdiUnderMahalle(string $mahalleId, string $adi): ?string
    {
        $mahalleId = trim($mahalleId);
        $adi = trim($adi);
        if ($mahalleId === '' || $adi === '') {
            return null;
        }
        $query = 'SELECT id FROM ' . $this->_tbl
            . ' WHERE ust_id = ? AND tip = ? AND adi = ? LIMIT 1';
        $row = $this->db->fetchObjectPrepared($query, [$mahalleId, 'sokak', $adi]);
        if (!$row || empty($row->id)) {
            return null;
        }

        return (string) $row->id;
    }

    /**
     * Kapı no için doğal sıra (1, 2, 10); mahalle/sokak/ilçe için alfabetik.
     */
    public static function compareAdiForTip(string $tip, string $a, string $b): int
    {
        if ((string) $tip === 'kapino') {
            return strnatcasecmp($a, $b);
        }
        return strcasecmp($a, $b);
    }

    /**
     * @param array<int, object> $list
     * @return array<int, object>
     */
    private static function sortObjectListByAdi(array $list, string $tip): array
    {
        usort($list, static function ($x, $y) use ($tip) {
            return self::compareAdiForTip(
                $tip,
                (string) ($x->adi ?? ''),
                (string) ($y->adi ?? '')
            );
        });
        return $list;
    }

    /**
     * @param array<int, array<string, mixed>> $list
     * @return array<int, array<string, mixed>>
     */
    private static function sortAssocListByAdi(array $list, string $tip): array
    {
        usort($list, static function ($x, $y) use ($tip) {
            return self::compareAdiForTip(
                $tip,
                (string) ($x['adi'] ?? ''),
                (string) ($y['adi'] ?? '')
            );
        });
        return $list;
    }

    /**
     * Denizli API ham yanıtını UTF-8 XML stringine çevirir (UTF-16 BOM / içerik).
     */
    private static function denizliXmlToUtf8($raw) {
        if ($raw === '' || $raw === null) {
            return '';
        }
        if (substr($raw, 0, 2) === "\xFF\xFE") {
            return mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
        }
        if (substr($raw, 0, 2) === "\xFE\xFF") {
            return mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE');
        }
        if (strpos($raw, "\0") !== false && strpos($raw, '<?xml') !== false) {
            return mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
        }
        return $raw;
    }

    /**
     * Admin vb.: API'den çek, DB'de olmayanları ekle (mevcut id'ler saveToDb ile korunur).
     *
     * @return array{ok: bool, mesaj?: string, api_kayit?: int}
     */
    public function mergeExternalChildren($parentId, $childType) {
        $childType = (string) $childType;
        if (!in_array($childType, ['mahalle', 'sokak', 'kapino'], true)) {
            return ['ok' => false, 'mesaj' => 'Geçersiz alt tip.'];
        }
        $parentId = trim((string) $parentId);
        if ($parentId === '') {
            return ['ok' => false, 'mesaj' => 'Üst kayıt belirtilmedi.'];
        }
        $p = $this->adminGetRowById($parentId);
        if (!$p) {
            return ['ok' => false, 'mesaj' => 'Üst kayıt bulunamadı.'];
        }
        $expectedParentTip = [
            'mahalle' => 'ilce',
            'sokak' => 'mahalle',
            'kapino' => 'sokak',
        ];
        if ((string) $p->tip !== $expectedParentTip[$childType]) {
            return ['ok' => false, 'mesaj' => 'Üst kayıt tipi uyuşmuyor.'];
        }
        $items = $this->fetchFromExternalService($parentId, $childType);
        return ['ok' => true, 'api_kayit' => count($items)];
    }

    /**
     * Denizli belediye adres servisinden mahalle / sokak / kapı listesi çeker ve DB'ye yazar.
     */
    private function fetchFromExternalService($parentId, $type) {
        $tApi = ($type === 'kapino') ? 'kapi' : $type;
        if (!in_array($tApi, ['mahalle', 'sokak', 'kapi'], true)) {
            return [];
        }

        $url = 'https://adres.denizli.bel.tr/veriHazirla.ashx?id=' . rawurlencode((string) $parentId) . '&t=' . rawurlencode($tApi);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 45,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        if ($raw === false || $raw === '') {
            return [];
        }

        $utf8 = self::denizliXmlToUtf8($raw);
        $utf8 = preg_replace('/<\?xml\s[^?]*\?>/i', '<?xml version="1.0" encoding="UTF-8"?>', $utf8, 1);

        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($utf8);
        if ($sx === false) {
            return [];
        }

        $items = [];

        if ($type === 'mahalle') {
            foreach ($sx->Mahalle as $node) {
                $id = trim((string) $node->ID);
                $adi = trim((string) $node->ADI);
                if ($id === '' || $adi === '') {
                    continue;
                }
                $this->saveToDb($id, $adi, $parentId, 'mahalle');
                $items[] = (object) ['id' => $id, 'adi' => $adi];
            }
        } elseif ($type === 'sokak') {
            foreach ($sx->Sokak as $node) {
                $id = trim((string) $node->ID);
                $adi = trim((string) $node->ADI);
                if ($id === '' || $adi === '') {
                    continue;
                }
                $this->saveToDb($id, $adi, $parentId, 'sokak');
                $items[] = (object) ['id' => $id, 'adi' => $adi];
            }
        } elseif ($type === 'kapino') {
            foreach ($sx->Kapi as $node) {
                $id = trim((string) $node->ID);
                $adi = trim((string) $node->NO);
                if ($adi === '' && isset($node->ADI)) {
                    $adi = trim((string) $node->ADI);
                }
                if ($id === '' || $adi === '') {
                    continue;
                }
                $this->saveToDb($id, $adi, $parentId, 'kapino');
                $items[] = (object) ['id' => $id, 'adi' => $adi];
            }
        }

        $sortTip = ($type === 'kapino') ? 'kapino' : 'mahalle';
        if ($type === 'sokak') {
            $sortTip = 'sokak';
        }
        $items = self::sortObjectListByAdi($items, $sortTip);

        return $items;
    }
    /**
    * Veritabanına kayıt fonksiyonu
    */
    public function saveToDb($id, $adi, $parentId, $type) {
        $id = trim((string) $id);
        $adi = trim((string) $adi);
        $parentId = (string) $parentId;
        $type = (string) $type;
        if ($id === '' || $adi === '') {
            return false;
        }

        $exists = $this->db->loadResultPrepared(
            'SELECT id FROM ' . $this->_tbl . ' WHERE id = ? AND ust_id = ? LIMIT 1',
            [$id, $parentId]
        );
        if ($exists) {
            return true;
        }

        $row = [
            'id' => $id,
            'adi' => $adi,
            'ust_id' => $parentId,
            'tip' => $type,
        ];

        return $this->db->insertPrepared($this->_tbl, $row) !== false;
    }
    
    public function getUserAddress($userid) {
        $sql = "SELECT i.adi AS ilce, m.adi as mahalle, s.adi as sokak, k.adi as kapino
                FROM #__hastalar as h
                LEFT JOIN #__adrestablosu AS i ON i.id=h.ilce
                LEFT JOIN #__adrestablosu AS m ON m.id=h.mahalle
                LEFT JOIN #__adrestablosu AS s ON s.id=h.sokak AND s.ust_id=h.mahalle
                LEFT JOIN #__adrestablosu AS k ON k.id=h.kapino AND k.ust_id=h.sokak
                WHERE h.id = ?";
        return $this->db->fetchObjectPrepared($sql, [(int) $userid]);
    }
    
    public function getAdresListeleri($patient) {
  
    $lists = array();

    // 1. İLÇE LİSTESİ
    $lists['ilce']    = $this->generateAdresSelect('ilce', null, $patient->ilce, 'İlçe', 'addr-main-ilce', 1);

    // 2. MAHALLE LİSTESİ (İlçeye bağlı)
    $lists['mahalle'] = $this->generateAdresSelect('mahalle', $patient->ilce, $patient->mahalle, 'Mahalle', 'addr-main-mahalle', 1);

    // 3. SOKAK LİSTESİ (Mahalleye bağlı)
    $lists['sokak']   = $this->generateAdresSelect('sokak', $patient->mahalle, $patient->sokak, 'Sokak', 'addr-main-sokak', 1);

    // 4. KAPINO LİSTESİ (Sokağa bağlı)
    $lists['kapino']  = $this->generateAdresSelect('kapino', $patient->sokak, $patient->kapino, 'Kapı No', 'addr-main-kapino', 1);
    
    $lists['adres_aciklama'] = $this->generateAdresAciklama('adres_aciklama', $patient->adres_aciklama, 'adres_aciklama');

    return $lists;
}

    public function getUserOtherAddresses($adresler) {
    // Adres listesinin dizi ve dolu olup olmadığını kontrol ediyoruz
    if (is_array($adresler) && !empty($adresler)) {
        
        $adresbilgi = array();
        
        foreach ($adresler as $v) {
            $sql = "SELECT 
                        i.adi AS ilce, 
                        m.adi AS mahalle, 
                        s.adi AS sokak, 
                        k.adi AS kapino
                    FROM #__adrestablosu AS i
                    LEFT JOIN #__adrestablosu AS m ON m.id = '{$v['mahalle']}'
                    LEFT JOIN #__adrestablosu AS s ON s.id = '{$v['sokak']}' AND s.ust_id = '{$v['mahalle']}'
                    LEFT JOIN #__adrestablosu AS k ON k.id = '{$v['kapino']}' AND k.ust_id = '{$v['sokak']}'
                    WHERE i.id = '{$v['ilce']}' 
                    LIMIT 1"; // Sadece ilgili satırı almak için limit ekledik
            
            $result['adres'] = $this->db->fetchObjectPrepared($sql);
            $result['adres_aciklama'] = $v['adres_aciklama'];
            if ($result) {
                $adresbilgi[] = $result;
            }
        }
        
        return $adresbilgi;
    }
    
    return array(); // Eğer adres yoksa boş dizi döndürmek daha güvenlidir
}
    /**
     * PHP4 için yardımcı SQL ve HTML oluşturucu fonksiyon
     */
    public function generateAdresSelect($tip, $ust_id, $current_val, $label, $element_id, $required=0) {    
        // SQL sorgusunu PHP4 standartlarında birleştiriyoruz 
        $kid = KurumAdresScope::effectiveKurumId();
        $query = "SELECT a.id, a.adi FROM #__adrestablosu AS a WHERE a.tip='" . $tip . "'";

        // Üst kayıt (UUID veya sayısal id); >0 kullanılmaz — UUID'ler yanlışlıkla boş listeye düşmesin
        $hasParent = ($ust_id !== null && $ust_id !== '' && (string) $ust_id !== '0');
        if ($hasParent) {
            $query .= ' AND a.ust_id=?';
            $genParams = [(string) $ust_id];
        } elseif ($tip !== 'ilce' && !$hasParent) {
            $query .= ' AND 1=2';
        }
        if ($kid !== null) {
            if ($tip === 'ilce') {
                $query .= KurumAdresScope::sqlFilterForTip('ilce', 'a', $kid);
            } elseif ($hasParent && in_array($tip, ['mahalle', 'sokak', 'kapino'], true)) {
                $query .= KurumAdresScope::sqlFilterForTip($tip, 'a', $kid, (string) $ust_id);
            }
        }

        $query .= " ORDER BY a.adi ASC";

        $rows = $this->db->fetchObjectListPrepared($query, $genParams ?? []);
        if (!is_array($rows)) {
            $rows = [];
        }
        $rows = KurumAdresScope::ensureCurrentInList($rows, $current_val !== null ? (string) $current_val : '', $tip);
        if ((string) $tip === 'kapino') {
            $rows = self::sortObjectListByAdi($rows, 'kapino');
        }
        
        $options[] = \App\Helpers\FormHelper::makeOption('', $label.' Seçin');
        foreach($rows as $row) {
         $options[] = \App\Helpers\FormHelper::makeOption($row->id, $row->adi);
        }

        $cascadeClass = [
            'ilce' => 'ilce-trigger',
            'mahalle' => 'mahalle-target mahalle-trigger',
            'sokak' => 'sokak-target sokak-trigger',
            'kapino' => 'kapino-target',
        ];
        $extraClass = $cascadeClass[$tip] ?? '';
        $req = $required ? 'required="required"' : '';
        $tagAttribs = trim($req . ' class="form-select ' . $extraClass . '"');

        return \App\Helpers\FormHelper::selectList($options, $tip, $tagAttribs, 'value', 'text', $current_val, $element_id);
    }

    public function generateAdresAciklama($name, $value, $element_id) {
        // HTML stringini PHP4 uyumlu birleştiriyoruz
        $html = '<textarea name="' . $name . '" id="' . $element_id . '" class="form-control" rows="2" placeholder="Adres detayı (Bina adı, Kat, Daire vb.)">' . $value . '</textarea>';
        return $html;
    }

    /**
     * Yönetim paneli için UUID v4 üretir.
     */
    public static function generateUuidV4() {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    /**
     * Mahalle `gun` alanındaki haftanın günü kodlarını (0–6, virgüllü) Türkçe güne çevirir (planlama ekranı ile aynı kodlar).
     */
    public static function formatMahalleGunCsvTr(?string $gunCsv, bool $abbrev = false): string
    {
        $gunCsv = trim((string) ($gunCsv ?? ''));
        if ($gunCsv === '') {
            return '';
        }
        $map = $abbrev
            ? [
                '1' => 'Pzt', '2' => 'Sal', '3' => 'Çar', '4' => 'Per',
                '5' => 'Cum', '6' => 'Cmt', '0' => 'Paz',
            ]
            : [
                '1' => 'Pazartesi', '2' => 'Salı', '3' => 'Çarşamba', '4' => 'Perşembe',
                '5' => 'Cuma', '6' => 'Cumartesi', '0' => 'Pazar',
            ];
        $order = ['1', '2', '3', '4', '5', '6', '0'];
        $parts = array_unique(array_filter(array_map('trim', explode(',', $gunCsv)), static function ($p) {
            return $p !== '';
        }));
        $labels = [];
        foreach ($order as $k) {
            if (in_array($k, $parts, true) && isset($map[$k])) {
                $labels[] = $map[$k];
            }
        }
        return implode(', ', $labels);
    }

    /**
     * @return object|null id, adi, tip, ust_id
     */
    public function getRowById($id, $ustId = null) {
        if ($id === null || $id === '') {
            return null;
        }
        $idStr = (string) $id;
        $ustTrim = $ustId !== null ? trim((string) $ustId) : '';
        if ($ustTrim !== '') {
            $sql = 'SELECT id, adi, tip, ust_id FROM ' . $this->_tbl
                . ' WHERE id = ? AND ust_id = ? LIMIT 1';
            $row = $this->db->fetchObjectPrepared($sql, [$idStr, $ustTrim]);
            return is_object($row) ? $row : null;
        }
        $sql = 'SELECT id, adi, tip, ust_id FROM ' . $this->_tbl . ' WHERE id = ? LIMIT 2';
        $rows = $this->db->fetchObjectListPrepared($sql, [$idStr]);
        if (!is_array($rows) || $rows === []) {
            return null;
        }
        $row = $rows[0];
        return is_object($row) ? $row : null;
    }

    /**
     * Admin erişimi — coğrafi kayıtlar platform geneli.
     */
    public function adminGetRowById($id, $ustId = null): ?object
    {
        return $this->getRowById($id, $ustId);
    }

    /**
     * Admin liste: tip başına birleşik satırlar (üst adları için JOIN).
     *
     * @return array<int, object>
     */
    public function adminListForTip($tip) {
        switch ($tip) {
            case 'ilce':
                $sql = 'SELECT a.id, a.adi, a.ust_id, a.tip FROM ' . $this->_tbl . ' a WHERE a.tip = ? ORDER BY a.adi ASC';
                break;
            case 'mahalle':
                $sql = 'SELECT a.id, a.adi, a.ust_id, a.tip, p.adi AS parent_adi
                    FROM ' . $this->_tbl . ' a
                    LEFT JOIN ' . $this->_tbl . ' p ON p.id = a.ust_id
                    WHERE a.tip = ? ORDER BY COALESCE(p.adi, \'\'), a.adi ASC';
                break;
            case 'sokak':
                $sql = 'SELECT a.id, a.adi, a.ust_id, a.tip, m.adi AS mahalle_adi, i.adi AS ilce_adi
                    FROM ' . $this->_tbl . ' a
                    LEFT JOIN ' . $this->_tbl . ' m ON m.id = a.ust_id AND m.tip = \'mahalle\'
                    LEFT JOIN ' . $this->_tbl . ' i ON i.id = m.ust_id AND i.tip = \'ilce\'
                    WHERE a.tip = ? ORDER BY COALESCE(i.adi, \'\'), COALESCE(m.adi, \'\'), a.adi ASC';
                break;
            case 'kapino':
                $sql = 'SELECT a.id, a.adi, a.ust_id, a.tip, s.adi AS sokak_adi, m.adi AS mahalle_adi, i.adi AS ilce_adi
                    FROM ' . $this->_tbl . ' a
                    LEFT JOIN ' . $this->_tbl . ' s ON s.id = a.ust_id AND s.tip = \'sokak\'
                    LEFT JOIN ' . $this->_tbl . ' m ON m.id = s.ust_id AND m.tip = \'mahalle\'
                    LEFT JOIN ' . $this->_tbl . ' i ON i.id = m.ust_id AND i.tip = \'ilce\'
                    WHERE a.tip = ? ORDER BY COALESCE(i.adi, \'\'), COALESCE(m.adi, \'\'), COALESCE(s.adi, \'\'), a.adi ASC';
                break;
            default:
                return [];
        }
        return $this->db->fetchObjectListPrepared($sql, [$tip]);
    }

    /**
     * Üst kayıt seçimi için (aynı tablodan parent tip).
     *
     * @return array<int, object>
     */
    public function adminParentsForChildTip($childTip) {
        $map = [
            'mahalle' => 'ilce',
            'sokak' => 'mahalle',
            'kapino' => 'sokak',
        ];
        if (!isset($map[$childTip])) {
            return [];
        }
        $parentTip = $map[$childTip];
        $sql = 'SELECT id, adi FROM ' . $this->_tbl . ' WHERE tip = ? ORDER BY adi ASC';
        return $this->db->fetchObjectListPrepared($sql, [$parentTip]);
    }

    public function adminChildCount($id, $ustId = null) {
        if ($id === null || $id === '') {
            return 0;
        }
        $ustTrim = $ustId !== null ? trim((string) $ustId) : '';
        if ($ustTrim !== '') {
            $row = $this->getRowById($id, $ustTrim);
            if ($row && (string) ($row->tip ?? '') === 'sokak') {
                $sqlOther = 'SELECT COUNT(*) FROM ' . $this->_tbl
                    . ' WHERE tip = ? AND id = ? AND ust_id <> ?';
                if ((int) $this->db->loadResultPrepared($sqlOther, ['sokak', (string) $id, $ustTrim]) > 0) {
                    return 0;
                }
            }
        }
        $idStr = (string) $id;
        $sql = 'SELECT COUNT(*) FROM ' . $this->_tbl . ' WHERE ust_id = ?'
            . ' AND NOT (id = ust_id AND ust_id = ?)';
        return (int) $this->db->loadResultPrepared($sql, [$idStr, $idStr]);
    }

    /**
     * Ana hasta kaydında bu adres ID’si kullanılıyor mu (diger_adres JSON basit tarama).
     */
    public function adminPatientReferenceCount($id) {
        if ($id === null || $id === '') {
            return 0;
        }
        $idStr = (string) $id;
        $sql = 'SELECT COUNT(*) FROM #__hastalar WHERE (ilce = ? OR mahalle = ? OR sokak = ? OR kapino = ?)';
        $n = (int) $this->db->loadResultPrepared($sql, [$idStr, $idStr, $idStr, $idStr]);
        $sql2 = 'SELECT COUNT(*) FROM #__hastalar WHERE diger_adres IS NOT NULL AND diger_adres != \'\' AND diger_adres LIKE ?';
        $n2 = (int) $this->db->loadResultPrepared($sql2, ['%' . $idStr . '%']);
        return $n + $n2;
    }

    public function adminInsertRow($id, $adi, $ustId, $tip) {
        $id = trim((string) $id);
        $adi = trim((string) $adi);
        $tip = (string) $tip;
        if ($tip === 'ilce') {
            $ustId = '0';
        } else {
            $ustId = trim((string) $ustId);
        }
        if ($id === '' || $adi === '' || $tip === '') {
            return false;
        }
        if ($tip !== 'ilce' && $ustId === '') {
            return false;
        }
        $row = [
            'id' => $id,
            'adi' => $adi,
            'ust_id' => $ustId,
            'tip' => $tip,
        ];
        return $this->db->insertPrepared($this->_tbl, $row) !== false;
    }

    public function adminUpdateRow($id, $adi, $ustId, $tip) {
        $id = trim((string) $id);
        $adi = trim((string) $adi);
        $tip = (string) $tip;
        if ($tip === 'ilce') {
            $ustId = '0';
        } else {
            $ustId = trim((string) $ustId);
        }
        if ($id === '' || $adi === '' || $tip === '') {
            return false;
        }
        if ($tip !== 'ilce' && $ustId === '') {
            return false;
        }
        return (bool) $this->db->executePrepared(
            'UPDATE ' . $this->_tbl . ' SET adi = ?, ust_id = ?, tip = ? WHERE id = ?',
            [$adi, $ustId, $tip, $id]
        );
    }

    public function adminDeleteById($id, $ustId = null) {
        if ($id === null || $id === '') {
            return false;
        }
        $idStr = (string) $id;
        $ustTrim = $ustId !== null ? trim((string) $ustId) : '';
        if ($ustTrim !== '') {
            return (bool) $this->db->executePrepared(
                'DELETE FROM ' . $this->_tbl . ' WHERE id = ? AND ust_id = ?',
                [$idStr, $ustTrim]
            );
        }

        return (bool) $this->db->executePrepared(
            'DELETE FROM ' . $this->_tbl . ' WHERE id = ?',
            [$idStr]
        );
    }

    /**
     * Hiyerarşik panel: belirli üst kayda bağlı çocuklar (id + adi).
     *
     * @return array<int, array<string, string>>
     */
    public function adminListByTipUst($tip, $ustId) {
        $tip = (string) $tip;
        $kid = KurumAdresScope::effectiveKurumId();
        $cols = $tip === 'kapino' ? 'a.id, a.adi, a.coords' : 'a.id, a.adi';
        if ($tip === 'ilce') {
            $sql = 'SELECT ' . $cols . ' FROM ' . $this->_tbl . ' AS a'
                . ' WHERE a.tip = ?';
            if ($kid !== null) {
                $sql .= KurumAdresScope::sqlFilterForTip('ilce', 'a', $kid);
            }
            $sql .= ' ORDER BY a.adi ASC';

            return $this->db->fetchAllPrepared($sql, ['ilce']);
        }
        $ustId = trim((string) $ustId);
        $sql = 'SELECT ' . $cols . ' FROM ' . $this->_tbl . ' AS a'
            . ' WHERE a.tip = ? AND a.ust_id = ?';
        if ($kid !== null && in_array($tip, ['mahalle', 'sokak', 'kapino'], true)) {
            $sql .= KurumAdresScope::sqlFilterForTip($tip, 'a', $kid, $ustId);
        }
        $sql .= ' ORDER BY a.adi ASC';
        $list = $this->db->fetchAllPrepared($sql, [$tip, $ustId]);
        if (!is_array($list)) {
            return [];
        }
        if ($tip === 'kapino') {
            return self::sortAssocListByAdi($list, 'kapino');
        }
        return $list;
    }

    /** @var array<string, string> */
    private static array $treeChildTipMap = [
        'ilce' => 'mahalle',
        'mahalle' => 'sokak',
        'sokak' => 'kapino',
    ];

    /**
     * Lazy ağaç: çocuk kayıtlar + alt düğüm var mı bayrağı.
     *
     * @return array<int, array<string, mixed>>
     */
    public function adminTreeChildren(string $tip, string $ustId = '0'): array {
        $tip = (string) $tip;
        $list = $this->adminListByTipUst($tip, $ustId);
        if (!is_array($list) || $list === []) {
            return [];
        }
        $childTip = self::$treeChildTipMap[$tip] ?? null;
        if ($childTip === null) {
            $out = [];
            foreach ($list as $row) {
                $out[] = [
                    'id' => (string) ($row['id'] ?? ''),
                    'adi' => (string) ($row['adi'] ?? ''),
                    'tip' => $tip,
                    'has_children' => false,
                ];
            }
            return $out;
        }
        $ids = [];
        foreach ($list as $row) {
            $id = trim((string) ($row['id'] ?? ''));
            if ($id !== '') {
                $ids[] = $id;
            }
        }
        $hasChildSet = $this->idsWithChildren($ids, $childTip);
        $out = [];
        foreach ($list as $row) {
            $id = (string) ($row['id'] ?? '');
            $out[] = [
                'id' => $id,
                'adi' => (string) ($row['adi'] ?? ''),
                'tip' => $tip,
                'has_children' => isset($hasChildSet[$id]),
            ];
        }
        return $out;
    }

    /**
     * @param list<string> $parentIds
     * @return array<string, true>
     */
    private function idsWithChildren(array $parentIds, string $childTip): array {
        $parentIds = array_values(array_unique(array_filter(array_map('strval', $parentIds))));
        if ($parentIds === []) {
            return [];
        }
        [$inSql, $inParams] = $this->db->whereInClause($parentIds);
        $sql = 'SELECT DISTINCT ust_id FROM ' . $this->_tbl
            . ' WHERE tip = ? AND ust_id IN (' . $inSql . ')';
        $rows = $this->db->fetchColumnListPrepared($sql, array_merge([$childTip], $inParams), 0);
        if (!is_array($rows)) {
            return [];
        }
        $set = [];
        foreach ($rows as $ustId) {
            $set[(string) $ustId] = true;
        }
        return $set;
    }

    /**
     * İlçe zinciri kırık mahalle / sokak / kapı sayıları.
     *
     * @return array{mahalle: int, sokak: int, kapino: int}
     */
    public function countOrphansWithoutIlce(): array {
        $out = ['mahalle' => 0, 'sokak' => 0, 'kapino' => 0];
        foreach (['mahalle', 'sokak', 'kapino'] as $tip) {
            $out[$tip] = $this->countOrphansForTip($tip);
        }
        return $out;
    }

    public function countOrphansForTip(string $tip): int {
        if (!in_array($tip, ['mahalle', 'sokak', 'kapino'], true)) {
            return 0;
        }
        $sql = 'SELECT COUNT(*) FROM (' . $this->orphanWithoutIlceSubquerySql($tip) . ') AS orphan_rows';
        return (int) $this->db->loadResultPrepared($sql);
    }

    /**
     * İlçe zinciri kırık kayıtlar (sayfalı).
     *
     * @param int|null $knownTotal Tarama sonrası bilinen toplam; verilirse ayrı COUNT atlanır.
     * @return array{items: array<int, array<string, string>>, total: int}
     */
    public function listOrphansWithoutIlce(string $tip, int $offset = 0, int $limit = 50, ?int $knownTotal = null): array {
        $tip = (string) $tip;
        if (!in_array($tip, ['mahalle', 'sokak', 'kapino'], true)) {
            return ['items' => [], 'total' => 0];
        }
        $offset = max(0, $offset);
        $limit = max(1, min(200, $limit));
        $sub = $this->orphanWithoutIlceSubquerySql($tip);
        if ($knownTotal !== null && $knownTotal >= 0) {
            $total = $knownTotal;
        } else {
            $total = (int) $this->db->loadResultPrepared('SELECT COUNT(*) FROM (' . $sub . ') AS orphan_rows');
        }
        $sql = 'SELECT * FROM (' . $sub . ') AS orphan_rows ORDER BY adi ASC'
            . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $rows = $this->db->fetchAllPrepared($sql);
        $items = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $items[] = [
                    'id' => (string) ($row['id'] ?? ''),
                    'adi' => (string) ($row['adi'] ?? ''),
                    'ust_id' => (string) ($row['ust_id'] ?? ''),
                    'tip' => $tip,
                    'reason' => (string) ($row['reason'] ?? 'no_ilce'),
                ];
            }
        }
        if ($offset === 0 && $items === [] && $total > 0) {
            $total = $this->countOrphansForTip($tip);
        } elseif ($offset > 0 && $items === [] && $total > 0) {
            $total = $this->countOrphansForTip($tip);
        }
        return ['items' => $items, 'total' => $total];
    }

    /**
     * Orphan kayıt silme — alt ağacı zorla temizler, sonra kendini siler (null = başarılı).
     */
    public function attemptDeleteOrphan(string $id, ?string $ustId = null): ?string {
        $visited = [];
        $this->forcePurgeOrphanNode($id, $ustId, $visited);
        $ustTrim = $ustId !== null ? trim($ustId) : '';
        if ($this->adminGetRowById($id, $ustTrim !== '' ? $ustTrim : null) === null) {
            return null;
        }
        return 'Silme işlemi başarısız.';
    }

    /**
     * Orphan alt ağacını özyinelemeli ve zorunlu siler (hasta/alt kayıt kontrolü yok).
     *
     * @param array<string, true> $visited
     */
    private function forcePurgeOrphanNode(string $id, ?string $ustId, array &$visited): void {
        $id = trim($id);
        if ($id === '') {
            return;
        }
        $ustTrim = $ustId !== null ? trim($ustId) : '';
        $visitKey = $id . "\0" . $ustTrim;
        if (isset($visited[$visitKey])) {
            return;
        }
        $visited[$visitKey] = true;

        $row = $this->adminGetRowById($id, $ustTrim !== '' ? $ustTrim : null);
        if (!$row) {
            return;
        }
        $tip = (string) ($row->tip ?? '');
        if (!in_array($tip, ['mahalle', 'sokak', 'kapino'], true)) {
            return;
        }
        $effectiveUstId = $ustTrim !== '' ? $ustTrim : trim((string) ($row->ust_id ?? ''));
        if ($effectiveUstId === '') {
            $effectiveUstId = null;
        }

        for ($round = 0; $round < 32; $round++) {
            $children = $this->adminListDirectChildrenByParentId($id);
            if ($children === []) {
                break;
            }
            foreach ($children as $child) {
                $childUst = trim((string) ($child['ust_id'] ?? ''));
                $this->forcePurgeOrphanNode(
                    (string) ($child['id'] ?? ''),
                    $childUst !== '' ? $childUst : null,
                    $visited
                );
            }
        }

        $this->adminDeleteById($id, $effectiveUstId);
    }

    /**
     * ust_id = parentId olan doğrudan alt kayıtlar (özyinelemeli silme için).
     *
     * @return list<array{id: string, ust_id: string, tip: string}>
     */
    private function adminListDirectChildrenByParentId(string $parentId): array {
        $parentId = trim($parentId);
        if ($parentId === '') {
            return [];
        }
        $q = $parentId;
        $sql = 'SELECT id, ust_id, tip FROM ' . $this->_tbl
            . ' WHERE ust_id = ?'
            . ' AND NOT (id = ? AND ust_id = ?)'
            . ' ORDER BY FIELD(tip, \'kapino\', \'sokak\', \'mahalle\', \'ilce\')';
        $rows = $this->db->fetchAllPrepared($sql, [$q, $q, $q]);
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (string) ($row['id'] ?? ''),
                'ust_id' => (string) ($row['ust_id'] ?? ''),
                'tip' => (string) ($row['tip'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Tek tur orphan temizliği: listele → güvenli sil.
     *
     * @return array{
     *   deleted: list<string>,
     *   failed: list<array{id: string, mesaj: string}>,
     *   skipped_has_children: int,
     *   skipped_patient_ref: int,
     *   remaining_estimate: int,
     *   batch_fetched: int
     * }
     */
    public function purgeOrphansBatch(string $tip, int $limit): array {
        if (!in_array($tip, ['mahalle', 'sokak', 'kapino'], true)) {
            return [
                'deleted' => [],
                'failed' => [],
                'skipped_has_children' => 0,
                'skipped_patient_ref' => 0,
                'remaining_estimate' => 0,
                'batch_fetched' => 0,
            ];
        }
        $limit = max(1, min(500, $limit));
        $result = $this->listOrphansWithoutIlce($tip, 0, $limit, null);
        $deleted = [];
        $failed = [];
        $skippedHasChildren = 0;
        $skippedPatientRef = 0;
        foreach ($result['items'] as $item) {
            $id = (string) ($item['id'] ?? '');
            $ustRaw = trim((string) ($item['ust_id'] ?? ''));
            $ustId = $ustRaw !== '' ? $ustRaw : null;
            if ($id === '') {
                continue;
            }
            $err = $this->attemptDeleteOrphan($id, $ustId);
            if ($err === null) {
                $deleted[] = $id;
                continue;
            }
            $failed[] = ['id' => $id, 'mesaj' => $err];
            if (str_contains($err, 'altında kayıtlar')) {
                $skippedHasChildren++;
            } elseif (str_contains($err, 'hasta kayıtlarında')) {
                $skippedPatientRef++;
            }
        }
        $total = (int) ($result['total'] ?? 0);
        $remaining = max(0, $total - count($deleted));

        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'skipped_has_children' => $skippedHasChildren,
            'skipped_patient_ref' => $skippedPatientRef,
            'remaining_estimate' => $remaining,
            'batch_fetched' => count($result['items']),
        ];
    }

    private function orphanSelfReferenceSql(string $alias): string {
        return '(' . $alias . '.id = ' . $alias . '.ust_id'
            . ' AND ' . $alias . '.ust_id IS NOT NULL'
            . ' AND TRIM(' . $alias . '.ust_id) <> \''
            . ' AND ' . $alias . '.ust_id <> \'0\')';
    }

    private function orphanReasonSelfReference(string $alias): string {
        return ' WHEN ' . $this->orphanSelfReferenceSql($alias)
            . ' THEN \'self_parent';
    }

    private function orphanWithoutIlceSubquerySql(string $tip): string {
        if ($tip === 'mahalle') {
            return 'SELECT m.id, m.adi, m.ust_id, '
                . $this->orphanReasonCaseMahalle()
                . ' AS reason FROM ' . $this->_tbl . ' AS m'
                . ' LEFT JOIN ' . $this->_tbl . ' AS p ON p.id = m.ust_id'
                . ' LEFT JOIN ' . $this->_tbl . ' AS i ON i.id = m.ust_id AND i.tip = \'ilce'
                . ' WHERE m.tip = \'mahalle'
                . ' AND (i.id IS NULL OR ' . $this->orphanSelfReferenceSql('m') . ')';
        }
        if ($tip === 'sokak') {
            return 'SELECT s.id, s.adi, s.ust_id, '
                . $this->orphanReasonCaseSokak()
                . ' AS reason FROM ' . $this->_tbl . ' AS s'
                . ' LEFT JOIN ' . $this->_tbl . ' AS m ON m.id = s.ust_id'
                . ' LEFT JOIN ' . $this->_tbl . ' AS i ON i.id = m.ust_id AND i.tip = \'ilce'
                . ' WHERE s.tip = \'sokak'
                . ' AND ('
                . 's.ust_id IS NULL OR TRIM(s.ust_id) = \'\' OR s.ust_id = \'0'
                . ' OR m.id IS NULL OR m.tip <> \'mahalle'
                . ' OR i.id IS NULL'
                . ' OR ' . $this->orphanSelfReferenceSql('s')
                . ')';
        }
        return 'SELECT k.id, k.adi, k.ust_id, '
            . $this->orphanReasonCaseKapino()
            . ' AS reason FROM ' . $this->_tbl . ' AS k'
            . ' LEFT JOIN ' . $this->_tbl . ' AS s ON s.id = k.ust_id'
            . ' LEFT JOIN ' . $this->_tbl . ' AS m ON m.id = s.ust_id'
            . ' LEFT JOIN ' . $this->_tbl . ' AS i ON i.id = m.ust_id AND i.tip = \'ilce'
            . ' WHERE k.tip = \'kapino'
            . ' AND ('
            . 'k.ust_id IS NULL OR TRIM(k.ust_id) = \'\' OR k.ust_id = \'0'
            . ' OR s.id IS NULL OR s.tip <> \'sokak'
            . ' OR m.id IS NULL OR m.tip <> \'mahalle'
            . ' OR i.id IS NULL'
            . ' OR ' . $this->orphanSelfReferenceSql('k')
            . ')';
    }

    private function orphanReasonCaseMahalle(): string {
        return 'CASE'
            . $this->orphanReasonSelfReference('m')
            . ' WHEN m.ust_id IS NULL OR TRIM(m.ust_id) = \'\' OR m.ust_id = \'0'
            . ' THEN \'no_parent'
            . ' WHEN p.id IS NULL THEN \'parent_missing'
            . ' WHEN p.tip <> \'ilce\' THEN \'wrong_parent_type'
            . ' ELSE \'no_ilce'
            . ' END';
    }

    private function orphanReasonCaseSokak(): string {
        return 'CASE'
            . $this->orphanReasonSelfReference('s')
            . ' WHEN s.ust_id IS NULL OR TRIM(s.ust_id) = \'\' OR s.ust_id = \'0'
            . ' THEN \'no_parent'
            . ' WHEN m.id IS NULL THEN \'parent_missing'
            . ' WHEN m.tip <> \'mahalle\' THEN \'wrong_parent_type'
            . ' ELSE \'no_ilce'
            . ' END';
    }

    private function orphanReasonCaseKapino(): string {
        return 'CASE'
            . $this->orphanReasonSelfReference('k')
            . ' WHEN k.ust_id IS NULL OR TRIM(k.ust_id) = \'\' OR k.ust_id = \'0'
            . ' THEN \'no_parent'
            . ' WHEN s.id IS NULL THEN \'parent_missing'
            . ' WHEN s.tip <> \'sokak\' THEN \'wrong_parent_type'
            . ' WHEN m.id IS NULL THEN \'parent_missing'
            . ' WHEN m.tip <> \'mahalle\' THEN \'wrong_parent_type'
            . ' ELSE \'no_ilce'
            . ' END';
    }

    /**
     * SQL: kapı koordinatı öncelikli, yoksa hasta.coords (geriye dönük).
     */
    public static function effectiveCoordsExpr(string $hAlias = 'h', string $kAlias = 'k'): string {
        return 'COALESCE(NULLIF(TRIM(' . $kAlias . '.coords), \'\'), NULLIF(TRIM(' . $hAlias . '.coords), \'\'))';
    }

    public static function effectiveCoordsWhereClause(string $hAlias = 'h', string $kAlias = 'k'): string {
        return '(' . $kAlias . '.has_coords = 1'
            . ' OR (' . $hAlias . '.coords IS NOT NULL AND TRIM(' . $hAlias . '.coords) <> \'\'))';
    }

    /** Kapı kaydı: koordinat boş (has_coords indeksi; TRIM taraması yok). */
    private function sqlKapinoMissingCoords(): string
    {
        return 'has_coords = 0';
    }

    public static function kapinoJoinSql(string $hAlias = 'h', string $kAlias = 'k'): string {
        return 'LEFT JOIN ' . '#__adrestablosu AS ' . $kAlias
            . ' ON ' . $kAlias . '.id = ' . $hAlias . '.kapino AND ' . $kAlias . '.tip = ' . "'kapino'";
    }

    /**
     * Hasta için gösterim / harita: kapı coords, yoksa eski hasta alanı.
     */
    public static function resolveCoordsForPatient(object $patient): string {
        $kapinoId = trim((string) ($patient->kapino ?? ''));
        if ($kapinoId !== '') {
            $row = (new self())->getRowById($kapinoId);
            if ($row) {
                $kc = trim((string) ($row->coords ?? ''));
                if ($kc !== '') {
                    return $kc;
                }
            }
        }
        return trim((string) ($patient->coords ?? ''));
    }

    /**
     * Kapı coords yazıldığında günlük TomTom kota dosyasını günceller (yalnızca gerçek değişim).
     */
    private function recordTomtomQuotaOnKapinoCoordsPersist(?string $previousRaw, string $newNormalized): void {
        $newNormalized = self::normalizeCoordsString($newNormalized);
        if ($newNormalized === '') {
            return;
        }
        $prev = self::normalizeCoordsString((string) $previousRaw);
        if ($prev !== '' && self::coordsAreEqual($prev, $newNormalized)) {
            return;
        }
        TomtomGeocodeQuotaHelper::recordKapinoCoordsPersisted();
    }

    public function setKapinoCoords(string $kapinoId, string $coords): bool {
        $kapinoId = trim($kapinoId);
        $coords = self::normalizeCoordsString($coords);
        if ($kapinoId === '') {
            return false;
        }
        $row = $this->getRowById($kapinoId);
        if (!$row || (string) $row->tip !== 'kapino') {
            return false;
        }
        $prevCoords = (string) ($row->coords ?? '');
        if ($coords === '') {
            $ok = (bool) $this->db->executePrepared(
                'UPDATE ' . $this->_tbl . ' SET coords = NULL, has_coords = 0 WHERE id = ? AND tip = ?',
                [$kapinoId, 'kapino']
            );
        } else {
            $ok = (bool) $this->db->executePrepared(
                'UPDATE ' . $this->_tbl . ' SET coords = ?, has_coords = 1 WHERE id = ? AND tip = ?',
                [$coords, $kapinoId, 'kapino']
            );
        }
        if ($ok) {
            $this->invalidateKapinoCoordStatsCache();
            if ($coords !== '') {
                $this->recordTomtomQuotaOnKapinoCoordsPersist($prevCoords, $coords);
            }
        }
        return $ok;
    }

    public function adminUpdateKapinoRow(string $id, string $adi, ?string $coords, $ustId = null): bool {
        $id = trim($id);
        $adi = trim($adi);
        if ($id === '' || $adi === '') {
            return false;
        }
        $row = $this->adminGetRowById($id, $ustId);
        if (!$row || (string) $row->tip !== 'kapino') {
            return false;
        }
        $prevCoords = (string) ($row->coords ?? '');
        $coordsNorm = $coords === null ? null : self::normalizeCoordsString($coords);
        $params = [$adi, $id];
        if ($coordsNorm === '' || $coordsNorm === null) {
            $sql = 'UPDATE ' . $this->_tbl . ' SET adi = ?, coords = NULL, has_coords = 0 WHERE id = ?';
        } else {
            $sql = 'UPDATE ' . $this->_tbl . ' SET adi = ?, coords = ?, has_coords = 1 WHERE id = ?';
            array_splice($params, 1, 0, [$coordsNorm]);
        }
        $ustTrim = $ustId !== null ? trim((string) $ustId) : '';
        if ($ustTrim !== '') {
            $sql .= ' AND ust_id = ?';
            $params[] = $ustTrim;
        }
        $ok = (bool) $this->db->executePrepared($sql, $params);
        if ($ok) {
            $this->invalidateKapinoCoordStatsCache();
            if ($coordsNorm !== null && $coordsNorm !== '') {
                $this->recordTomtomQuotaOnKapinoCoordsPersist($prevCoords, $coordsNorm);
            }
        }
        return $ok;
    }

    /**
     * TomTom geocode için kapı + üst hiyerarşi metni.
     */
    public function buildGeocodeQueryForKapinoId(string $kapinoId, ?string $adresAciklama = null): ?string {
        $kapinoId = trim($kapinoId);
        if ($kapinoId === '') {
            return null;
        }
        $sql = 'SELECT i.adi AS ilce, m.adi AS mahalle, s.adi AS sokak, k.adi AS kapino
                FROM ' . $this->_tbl . ' AS k
                LEFT JOIN ' . $this->_tbl . ' AS s ON s.id = k.ust_id
                LEFT JOIN ' . $this->_tbl . ' AS m ON m.id = s.ust_id
                LEFT JOIN ' . $this->_tbl . ' AS i ON i.id = m.ust_id
                WHERE k.id = ? AND k.tip = ?';
        $row = $this->db->fetchObjectPrepared($sql, [$kapinoId, 'kapino']);
        if (!$row) {
            return null;
        }
        $parts = [];
        foreach (['mahalle', 'sokak', 'kapino', 'ilce'] as $f) {
            $val = trim((string) ($row->$f ?? ''));
            if ($val !== '') {
                $parts[] = $val;
            }
        }
        $adresAciklama = trim((string) $adresAciklama);
        if ($adresAciklama !== '') {
            $parts[] = $adresAciklama;
        }
        $parts[] = 'Denizli';
        $parts[] = 'Turkey';
        $parts = array_values(array_unique($parts));
        return $parts === [] ? null : implode(', ', $parts);
    }

    public static function normalizeCoordsString(string $coords): string {
        $coords = trim(str_replace(' ', '', $coords));
        if ($coords === '') {
            return '';
        }
        $parts = explode(',', $coords, 2);
        if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
            return '';
        }
        return number_format((float) $parts[0], 6, '.', '') . ','
            . number_format((float) $parts[1], 6, '.', '');
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    private function tomtomGeocodeFirstResult(string $addressQuery): ?array
    {
        $apiKey = defined('TOMTOM_KEY') ? trim((string) TOMTOM_KEY) : '';
        if ($apiKey === '') {
            return null;
        }
        $url = 'https://api.tomtom.com/search/2/geocode/' . rawurlencode($addressQuery) . '.json'
            . '?key=' . rawurlencode($apiKey)
            . '&limit=1&language=tr-TR&countrySet=TR';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'ESH-AdresCoords/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200 || !$response) {
            return null;
        }
        $data = json_decode($response, true);
        $position = $data['results'][0]['position'] ?? null;
        if (!is_array($position) || !isset($position['lat'], $position['lon'])) {
            return null;
        }
        return [
            'lat' => (float) $position['lat'],
            'lon' => (float) $position['lon'],
        ];
    }

    /**
     * TomTom sonucu ile kayıtlı coords aynı mı (normalize edilmiş).
     */
    public static function coordsAreEqual(?string $stored, ?string $fresh): bool
    {
        $a = self::normalizeCoordsString((string) $stored);
        $b = self::normalizeCoordsString((string) $fresh);
        if ($a === '' || $b === '') {
            return false;
        }
        return $a === $b;
    }

    /**
     * Tek kapı: TomTom ile güncel konum; kayıtlı ile farklıysa veya boşsa yazar.
     *
     * @return array{ok: bool, coords?: string, changed?: bool, was_empty?: bool, mesaj?: string}
     */
    public function reconcileKapinoCoordsById(string $kapinoId): array
    {
        $kapinoId = trim($kapinoId);
        if ($kapinoId === '') {
            return ['ok' => false, 'mesaj' => 'Kapı kaydı belirtilmedi.'];
        }
        $row = $this->getRowById($kapinoId);
        if (!$row || (string) $row->tip !== 'kapino') {
            return ['ok' => false, 'mesaj' => 'Geçersiz kapı kaydı.'];
        }
        $storedNorm = self::normalizeCoordsString((string) ($row->coords ?? ''));
        $wasEmpty = $storedNorm === '';

        $query = $this->buildGeocodeQueryForKapinoId($kapinoId);
        if ($query === null) {
            return ['ok' => false, 'mesaj' => 'Adres metni oluşturulamadı.'];
        }
        if (defined('TOMTOM_KEY') && trim((string) TOMTOM_KEY) === '') {
            return ['ok' => false, 'mesaj' => 'TomTom API anahtarı tanımlı değil.'];
        }
        $position = $this->tomtomGeocodeFirstResult($query);
        if ($position === null) {
            return ['ok' => false, 'mesaj' => 'Koordinat bulunamadı.'];
        }
        $fresh = number_format((float) $position['lat'], 6, '.', '') . ','
            . number_format((float) $position['lon'], 6, '.', '');

        if (!$wasEmpty && self::coordsAreEqual($storedNorm, $fresh)) {
            return [
                'ok' => true,
                'coords' => $storedNorm,
                'changed' => false,
                'was_empty' => false,
            ];
        }

        if (!$this->setKapinoCoords($kapinoId, $fresh)) {
            return ['ok' => false, 'mesaj' => 'Koordinat kaydedilemedi.'];
        }

        return [
            'ok' => true,
            'coords' => $fresh,
            'changed' => true,
            'was_empty' => $wasEmpty,
        ];
    }

    /**
     * Tek kapı için TomTom geocode; coords yazar.
     *
     * @return array{ok: bool, coords?: string, mesaj?: string}
     */
    public function geocodeKapinoById(string $kapinoId, bool $force = false): array
    {
        if (!$force) {
            $row = $this->getRowById(trim($kapinoId));
            if ($row && trim((string) ($row->coords ?? '')) !== '') {
                return [
                    'ok' => true,
                    'coords' => self::normalizeCoordsString((string) $row->coords),
                ];
            }
        }
        $res = $this->reconcileKapinoCoordsById($kapinoId);
        if (empty($res['ok'])) {
            return ['ok' => false, 'mesaj' => $res['mesaj'] ?? 'Koordinat işlenemedi.'];
        }
        return ['ok' => true, 'coords' => $res['coords'] ?? ''];
    }

    /**
     * Sokak altı tüm kapılar: TomTom ile karşılaştır; eksik veya değişmişse güncelle.
     *
     * @return array{ok: bool, denenen: int, bulunan: int, guncellenen: int, ayni: int, kalan: int, last_id?: string}
     */
    public function syncKapinoCoordsUnderSokak(string $sokakId, int $limit = 35, string $afterId = ''): array
    {
        $sokakId = trim($sokakId);
        $afterId = trim($afterId);
        $limit = max(1, min(50, $limit));
        if ($sokakId === '') {
            return ['ok' => false, 'denenen' => 0, 'bulunan' => 0, 'guncellenen' => 0, 'ayni' => 0, 'kalan' => 0];
        }
        $sokak = $this->adminGetRowById($sokakId);
        if (!$sokak || (string) $sokak->tip !== 'sokak') {
            return ['ok' => false, 'denenen' => 0, 'bulunan' => 0, 'guncellenen' => 0, 'ayni' => 0, 'kalan' => 0];
        }

        $params = ['kapino', $sokakId];
        $sql = 'SELECT id FROM ' . $this->_tbl
            . ' WHERE tip = ? AND ust_id = ?';
        if ($afterId !== '') {
            $sql .= ' AND id > ?';
            $params[] = $afterId;
        }
        $sql .= ' ORDER BY id ASC LIMIT ' . (int) $limit;
        $rows = $this->db->fetchColumnListPrepared($sql, $params, 0);
        if (!is_array($rows)) {
            $rows = [];
        }

        $bulunan = 0;
        $guncellenen = 0;
        $ayni = 0;
        $denenen = 0;
        $lastId = $afterId;

        foreach ($rows as $kid) {
            $kid = (string) $kid;
            if ($kid === '') {
                continue;
            }
            $denenen++;
            $lastId = $kid;
            $res = $this->reconcileKapinoCoordsById($kid);
            if (!empty($res['ok'])) {
                if (!empty($res['changed'])) {
                    if (!empty($res['was_empty'])) {
                        $bulunan++;
                    } else {
                        $guncellenen++;
                    }
                } else {
                    $ayni++;
                }
            }
            if ($denenen < count($rows)) {
                usleep(120000);
            }
        }

        $kalan = 0;
        if ($lastId !== '') {
            $kalan = (int) $this->db->loadResultPrepared(
                'SELECT COUNT(*) FROM ' . $this->_tbl . ' WHERE tip = ? AND ust_id = ? AND id > ?',
                ['kapino', $sokakId, $lastId]
            );
        }

        $out = [
            'ok' => true,
            'denenen' => $denenen,
            'bulunan' => $bulunan,
            'guncellenen' => $guncellenen,
            'ayni' => $ayni,
            'kalan' => $kalan,
        ];
        if ($lastId !== '') {
            $out['last_id'] = $lastId;
        }
        return $out;
    }

    /**
     * Sokak altındaki coords boş kapılar için toplu geocode (geriye dönük; sync kullanın).
     *
     * @return array{ok: bool, denenen: int, bulunan: int, kalan: int}
     */
    public function geocodeKapinoMissingUnderSokak(string $sokakId, int $limit = 35): array
    {
        $sync = $this->syncKapinoCoordsUnderSokak($sokakId, $limit);
        return [
            'ok' => !empty($sync['ok']),
            'denenen' => (int) ($sync['denenen'] ?? 0),
            'bulunan' => (int) ($sync['bulunan'] ?? 0) + (int) ($sync['guncellenen'] ?? 0),
            'kalan' => (int) ($sync['kalan'] ?? 0),
        ];
    }

    public function countKapinoMissingCoordsUnderSokak(string $sokakId): int
    {
        $sokakId = trim($sokakId);
        if ($sokakId === '') {
            return 0;
        }
        return (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM ' . $this->_tbl
            . ' WHERE tip = ? AND ust_id = ? AND ' . $this->sqlKapinoMissingCoords(),
            ['kapino', $sokakId]
        );
    }

    /** Tüm kapı kayıtlarında coords boş olanların sayısı. */
    public function countKapinoMissingCoords(): int
    {
        return $this->getKapinoCoordStats()->missing;
    }

    public function countKapinoTotal(): int
    {
        return $this->getKapinoCoordStats()->total;
    }

    /**
     * Kapı koordinat özeti (tek sorgu; AdresKoordinat ana sayfa).
     */
    public function getKapinoCoordStats(): object
    {
        if ($this->kapinoCoordStatsCache !== null) {
            return $this->kapinoCoordStatsCache;
        }

        $fileCached = StatsQueryCache::get('kapino_coord_stats', 300);
        if (is_array($fileCached) && isset($fileCached['total'], $fileCached['missing'])) {
            $this->kapinoCoordStatsCache = (object) [
                'total' => (int) $fileCached['total'],
                'missing' => (int) $fileCached['missing'],
                'with_coords' => max(0, (int) $fileCached['total'] - (int) $fileCached['missing']),
            ];

            return $this->kapinoCoordStatsCache;
        }

        $row = $this->db->fetchObjectPrepared(
            'SELECT COUNT(*) AS total, SUM(has_coords = 0) AS missing'
            . ' FROM ' . $this->_tbl
            . ' WHERE tip = ?',
            ['kapino']
        );
        $total = (int) ($row->total ?? 0);
        $missing = (int) ($row->missing ?? 0);

        $this->kapinoCoordStatsCache = (object) [
            'total' => $total,
            'missing' => $missing,
            'with_coords' => max(0, $total - $missing),
        ];
        StatsQueryCache::set('kapino_coord_stats', [
            'total' => $total,
            'missing' => $missing,
        ], 300);

        return $this->kapinoCoordStatsCache;
    }

    private function invalidateKapinoCoordStatsCache(): void
    {
        $this->kapinoCoordStatsCache = null;
        StatsQueryCache::forget('kapino_coord_stats');
        StatsQueryCache::forget('harita_map_rows');
    }

    /**
     * Koordinatsız bir sonraki kapı id (id sırası).
     */
    public function fetchNextKapinoMissingCoordsId(string $afterId = ''): ?string
    {
        $afterId = trim($afterId);
        $params = ['kapino'];
        $sql = 'SELECT id FROM ' . $this->_tbl
            . ' WHERE tip = ? AND ' . $this->sqlKapinoMissingCoords();
        if ($afterId !== '') {
            $sql .= ' AND id > ?';
            $params[] = $afterId;
        }
        $sql .= ' ORDER BY id ASC LIMIT 1';
        $id = $this->db->loadResultPrepared($sql, $params);
        if ($id === null || $id === false || trim((string) $id) === '') {
            return null;
        }
        return (string) $id;
    }

    /**
     * Kapı + üst adres adları (log / tablo gösterimi).
     */
    public function getKapinoAddressSummary(string $kapinoId): ?object
    {
        $kapinoId = trim($kapinoId);
        if ($kapinoId === '') {
            return null;
        }
        $row = $this->db->fetchObjectPrepared(
            'SELECT i.adi AS ilce, m.adi AS mahalle, s.adi AS sokak, k.adi AS kapino
                FROM ' . $this->_tbl . ' AS k
                LEFT JOIN ' . $this->_tbl . ' AS s ON s.id = k.ust_id
                LEFT JOIN ' . $this->_tbl . ' AS m ON m.id = s.ust_id
                LEFT JOIN ' . $this->_tbl . ' AS i ON i.id = m.ust_id
                WHERE k.id = ? AND k.tip = ?',
            [$kapinoId, 'kapino']
        );
        return $row ?: null;
    }

    /**
     * Yalnızca görünen ad güncellenir (hiyerarşik modal düzenleme).
     */
    public function adminUpdateAdiOnly($id, $adi, $ustId = null) {
        $id = trim((string) $id);
        $adi = trim((string) $adi);
        if ($id === '' || $adi === '') {
            return false;
        }
        $params = [$adi, $id];
        $sql = 'UPDATE ' . $this->_tbl . ' SET adi = ? WHERE id = ?';
        $ustTrim = $ustId !== null ? trim((string) $ustId) : '';
        if ($ustTrim !== '') {
            $sql .= ' AND ust_id = ?';
            $params[] = $ustTrim;
        }
        return (bool) $this->db->executePrepared($sql, $params);
    }

    /**
     * Yeni kayıt için üst kayıt tipi doğru mu (ilce ← mahalle ← sokak ← kapino).
     */
    public function adminValidateParentForChild($childTip, $parentId) {
        $childTip = (string) $childTip;
        if ($childTip === 'ilce') {
            return true;
        }
        $parentId = trim((string) $parentId);
        if ($parentId === '') {
            return false;
        }
        $expect = [
            'mahalle' => 'ilce',
            'sokak' => 'mahalle',
            'kapino' => 'sokak',
        ];
        if (!isset($expect[$childTip])) {
            return false;
        }
        $row = $this->adminGetRowById($parentId);
        return $row && (string) $row->tip === $expect[$childTip];
    }

    /**
     * Yönetim haritası OSM GeoJSON filtresi: Pamukkale ve Merkezefendi ilçelerine bağlı mahalle adları (DISTINCT).
     * Tarafında `properties.name` ile eşleştirme için tam `adi` listesi döner.
     *
     * @return list<string>
     */
    public function getMahalleAdlariForHaritaGeoPamukkaleMerkezefendi(): array {
        $ilceAdlar = ['Pamukkale', 'Merkezefendi'];
        [$inSql, $inParams] = $this->db->whereInClause($ilceAdlar);
        $sql = 'SELECT DISTINCT m.adi AS adi FROM ' . $this->_tbl . ' AS m'
            . ' INNER JOIN ' . $this->_tbl . ' AS i ON i.id = m.ust_id AND i.tip = ?'
            . ' WHERE m.tip = ? AND TRIM(m.adi) <> ? AND i.adi IN (' . $inSql . ')'
            . ' ORDER BY m.adi ASC';
        $rows = $this->db->fetchAllPrepared($sql, array_merge(['ilce', 'mahalle', ''], $inParams));
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $a = isset($row['adi']) ? trim((string) $row['adi']) : '';
            if ($a !== '') {
                $out[] = $a;
            }
        }
        return $out;
    }
}