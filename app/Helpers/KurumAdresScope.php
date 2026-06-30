<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Models\Address;
use App\Models\KurumAdres;

/**
 * Kurum bazlı adres kapsamı — ilçe/mahalle/sokak atamaları ve kalıtım.
 */
final class KurumAdresScope
{
    /** @var array<int, bool> */
    private static array $hasAssignmentsCache = [];

    /** @var array<string, bool> */
    private static array $isAllowedCache = [];

    /** Oturum / üst menü kurum filtresine göre efektif kurum (null = tüm adresler). */
    public static function effectiveKurumId(): ?int
    {
        return TenantContext::filterKurumId();
    }

    public static function shouldFilter(?int $kurumId = null): bool
    {
        $kid = $kurumId ?? self::effectiveKurumId();
        if ($kid === null || $kid <= 0) {
            return false;
        }
        if (!KurumAdres::tableExists()) {
            return false;
        }

        return self::hasAssignments($kid);
    }

    public static function hasAssignments(int $kurumId): bool
    {
        if ($kurumId <= 0 || !KurumAdres::tableExists()) {
            return false;
        }
        if (array_key_exists($kurumId, self::$hasAssignmentsCache)) {
            return self::$hasAssignmentsCache[$kurumId];
        }
        $has = (new KurumAdres())->hasAssignments($kurumId);
        self::$hasAssignmentsCache[$kurumId] = $has;

        return $has;
    }

    public static function isAllowed(int $kurumId, string $adresId, ?string $parentId = null): bool
    {
        $adresId = trim($adresId);
        if ($kurumId <= 0 || $adresId === '') {
            return true;
        }
        if (!self::shouldFilter($kurumId)) {
            return true;
        }

        $parentKey = $parentId !== null ? trim($parentId) : '';
        $cacheKey = $kurumId . ':' . $adresId . ':' . $parentKey;
        if (array_key_exists($cacheKey, self::$isAllowedCache)) {
            return self::$isAllowedCache[$cacheKey];
        }

        $addr = new Address();
        $current = $parentKey !== ''
            ? $addr->getRowById($adresId, $parentKey)
            : $addr->getRowById($adresId);
        $allowed = false;
        $guard = 0;
        $model = new KurumAdres();

        while ($current && $guard < 10) {
            $id = trim((string) ($current->id ?? ''));
            $tip = (string) ($current->tip ?? '');
            if ($id !== '' && in_array($tip, ['ilce', 'mahalle', 'sokak'], true)) {
                $direct = $model->getDirectAssignmentIds($kurumId);
                if (in_array($id, $direct, true)) {
                    $allowed = true;
                    break;
                }
            }
            $ust = trim((string) ($current->ust_id ?? ''));
            if ($ust === '' || $ust === '0') {
                break;
            }
            $current = $addr->getRowById($ust);
            $guard++;
        }

        self::$isAllowedCache[$cacheKey] = $allowed;

        return $allowed;
    }

    /**
     * Hasta adres zinciri (ilçe, mahalle, sokak) kurum kapsamında mı?
     *
     * @param array{ilce?: string, mahalle?: string, sokak?: string} $parts
     */
    public static function assertPatientAddressParts(int $kurumId, array $parts): ?string
    {
        if (!self::shouldFilter($kurumId)) {
            return null;
        }

        foreach (['ilce', 'mahalle', 'sokak'] as $key) {
            $val = trim((string) ($parts[$key] ?? ''));
            if ($val === '') {
                continue;
            }
            $parentId = null;
            if ($key === 'mahalle') {
                $parentId = trim((string) ($parts['ilce'] ?? ''));
            } elseif ($key === 'sokak') {
                $parentId = trim((string) ($parts['mahalle'] ?? ''));
            }
            if (!self::isAllowed($kurumId, $val, $parentId !== '' ? $parentId : null)) {
                return 'Seçilen adres kurumunuzun yetkili coğrafi kapsamı dışındadır.';
            }
        }

        return null;
    }

    /** Kurum kapsamı dışındaysa hata metni; aksi halde null. */
    public static function denyUnlessAllowed(string $adresId, ?string $parentId = null): ?string
    {
        $kid = self::effectiveKurumId();
        if ($kid === null || !self::shouldFilter($kid)) {
            return null;
        }
        $adresId = trim($adresId);
        if ($adresId === '') {
            return null;
        }
        if (self::isAllowed($kid, $adresId, $parentId)) {
            return null;
        }

        return 'Bu adres kurumunuzun yetkili coğrafi kapsamı dışındadır.';
    }

    /**
     * SQL AND parçası — $alias #__adrestablosu satır alias'ı (ör. a, i, m).
     */
    public static function sqlFilterForTip(string $tip, string $alias, int $kurumId, ?string $parentId = null): string
    {
        if (!self::shouldFilter($kurumId)) {
            return '';
        }

        $kid = (int) $kurumId;
        $a = preg_replace('/[^a-zA-Z0-9_]/', '', $alias) ?: 'a';
        $parentId = $parentId !== null ? trim($parentId) : null;

        if ($tip === 'ilce') {
            return ' AND (' . $a . '.id IN (SELECT adres_id FROM #__kurum_adres WHERE kurum_id = ' . $kid . ' AND tip = ' . "'ilce'" . ')'
                . ' OR ' . $a . '.id IN (SELECT m.ust_id FROM #__adrestablosu m'
                . ' INNER JOIN #__kurum_adres ka ON ka.adres_id = m.id AND ka.kurum_id = ' . $kid . ' AND ka.tip = ' . "'mahalle'"
                . ' WHERE m.tip = ' . "'mahalle'" . ')'
                . ' OR ' . $a . '.id IN (SELECT m2.ust_id FROM #__adrestablosu s'
                . ' INNER JOIN #__adrestablosu m2 ON m2.id = s.ust_id AND m2.tip = ' . "'mahalle'"
                . ' INNER JOIN #__kurum_adres ka ON ka.adres_id = s.id AND ka.kurum_id = ' . $kid . ' AND ka.tip = ' . "'sokak'"
                . ' WHERE s.tip = ' . "'sokak'" . '))';
        }

        $db = Database::getInstance();

        if ($tip === 'mahalle' && $parentId !== null && $parentId !== '') {
            $pq = $db->quote($parentId);

            return ' AND (' . $pq . ' IN (SELECT adres_id FROM #__kurum_adres WHERE kurum_id = ' . $kid . ' AND tip = ' . "'ilce'" . ')'
                . ' OR ' . $a . '.id IN (SELECT adres_id FROM #__kurum_adres WHERE kurum_id = ' . $kid . ' AND tip = ' . "'mahalle'" . ')'
                . ' OR ' . $a . '.id IN (SELECT s.ust_id FROM #__adrestablosu s'
                . ' INNER JOIN #__kurum_adres ka ON ka.adres_id = s.id AND ka.kurum_id = ' . $kid . ' AND ka.tip = ' . "'sokak'"
                . ' WHERE s.tip = ' . "'sokak'" . '))';
        }

        if ($tip === 'sokak' && $parentId !== null && $parentId !== '') {
            $mq = $db->quote($parentId);

            return ' AND (' . $mq . ' IN (SELECT adres_id FROM #__kurum_adres WHERE kurum_id = ' . $kid . ' AND tip = ' . "'mahalle'" . ')'
                . ' OR ' . $a . '.id IN (SELECT adres_id FROM #__kurum_adres WHERE kurum_id = ' . $kid . ' AND tip = ' . "'sokak'" . ')'
                . ' OR EXISTS (SELECT 1 FROM #__kurum_adres ka WHERE ka.kurum_id = ' . $kid . ' AND ka.tip = ' . "'ilce'"
                . ' AND ka.adres_id = (SELECT ust_id FROM #__adrestablosu WHERE id = ' . $mq . ' AND tip = ' . "'mahalle'" . ' LIMIT 1)))';
        }

        if ($tip === 'kapino' && $parentId !== null && $parentId !== '') {
            $sq = $db->quote($parentId);

            return ' AND ' . self::isKapinoParentAllowedSql($sq, $kid);
        }

        return '';
    }

    private static function isKapinoParentAllowedSql(string $sokakIdQuoted, int $kurumId): string
    {
        return '(' . $sokakIdQuoted . ' IN (SELECT adres_id FROM #__kurum_adres WHERE kurum_id = ' . $kurumId . ' AND tip = ' . "'sokak'" . ')'
            . ' OR EXISTS (SELECT 1 FROM #__kurum_adres ka WHERE ka.kurum_id = ' . $kurumId . ' AND ka.tip = ' . "'mahalle'" . ' AND ka.adres_id = (SELECT ust_id FROM #__adrestablosu WHERE id = ' . $sokakIdQuoted . ' LIMIT 1))'
            . ' OR EXISTS (SELECT 1 FROM #__adrestablosu m'
            . ' INNER JOIN #__kurum_adres ka ON ka.adres_id = m.ust_id AND ka.kurum_id = ' . $kurumId . ' AND ka.tip = ' . "'ilce'"
            . ' WHERE m.id = (SELECT ust_id FROM #__adrestablosu WHERE id = ' . $sokakIdQuoted . ' LIMIT 1)'
            . ' AND m.tip = ' . "'mahalle'" . '))';
    }

    /**
     * Mahalle listesi — isteğe bağlı ilçe üst filtresi (planlama).
     */
    public static function sqlMahalleScope(string $alias, int $kurumId, ?string $ilceId = null): string
    {
        if (!self::shouldFilter($kurumId)) {
            return '';
        }
        $ilceId = $ilceId !== null ? trim($ilceId) : '';
        if ($ilceId !== '' && $ilceId !== '0') {
            return self::sqlFilterForTip('mahalle', $alias, $kurumId, $ilceId);
        }

        $a = preg_replace('/[^a-zA-Z0-9_]/', '', $alias) ?: 'm';
        $kid = (int) $kurumId;

        return ' AND (' . $a . '.id IN (SELECT adres_id FROM #__kurum_adres WHERE kurum_id = ' . $kid . ' AND tip = ' . "'mahalle'" . ')'
            . ' OR ' . $a . '.ust_id IN (SELECT adres_id FROM #__kurum_adres WHERE kurum_id = ' . $kid . ' AND tip = ' . "'ilce'" . ')'
            . ' OR ' . $a . '.id IN (SELECT s.ust_id FROM #__adrestablosu s'
            . ' INNER JOIN #__kurum_adres ka ON ka.adres_id = s.id AND ka.kurum_id = ' . $kid . ' AND ka.tip = ' . "'sokak'"
            . ' WHERE s.tip = ' . "'sokak'" . '))';
    }

    /**
     * Mevcut seçili değer filtre dışındaysa listeye ekle (düzenleme formu).
     *
     * @param list<object> $rows
     * @return list<object>
     */
    public static function ensureCurrentInList(array $rows, ?string $currentId, string $tip): array
    {
        $currentId = trim((string) $currentId);
        if ($currentId === '') {
            return $rows;
        }
        foreach ($rows as $row) {
            if ((string) ($row->id ?? '') === $currentId) {
                return $rows;
            }
        }
        $row = (new Address())->adminGetRowById($currentId);
        if ($row && (string) ($row->tip ?? '') === $tip) {
            $rows[] = $row;
        }

        return $rows;
    }
}
