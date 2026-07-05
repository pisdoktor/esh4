<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * USBS / e-Nabız uyum hazırlığı — alan eşlemesi ve referans normalizasyonu.
 */
final class UsbsComplianceHelper
{
    private const MAPPING_FILE = 'config/usbs-field-mapping.json';

    /** @var bool|null */
    private static $columnsReady = null;

    /** @var array<string, mixed>|null */
    private static $mappingCache = null;

    public static function columnsReady(): bool
    {
        if (self::$columnsReady !== null) {
            return self::$columnsReady;
        }
        try {
            $db = \App\Core\Database::getInstance();
            $tbl = $db->replacePrefix('#__izlemler');
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
                [$tbl, 'usbs_bildirim_durum']
            );
            self::$columnsReady = $row !== null && $row !== false && $row !== '';
        } catch (\Throwable $e) {
            self::$columnsReady = false;
        }

        return self::$columnsReady;
    }

    public static function enabled(): bool
    {
        return self::columnsReady();
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapping(): array
    {
        if (self::$mappingCache !== null) {
            return self::$mappingCache;
        }

        $path = ROOT_PATH . '/' . self::MAPPING_FILE;
        if (!is_readable($path)) {
            self::$mappingCache = [];

            return self::$mappingCache;
        }

        try {
            $raw = file_get_contents($path);
            $decoded = is_string($raw) ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : null;
            self::$mappingCache = is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            self::$mappingCache = [];
        }

        return self::$mappingCache;
    }

    public static function mappingTitle(): string
    {
        $m = self::mapping();

        return trim((string) ($m['title'] ?? 'USBS alan eşlemesi'));
    }

    public static function mappingDescription(): string
    {
        $m = self::mapping();

        return trim((string) ($m['description'] ?? ''));
    }

    /**
     * @return list<array{key: string, label: string, data: array<string, mixed>}>
     */
    public static function entitySections(): array
    {
        $m = self::mapping();
        $entities = $m['entities'] ?? [];
        if (!is_array($entities)) {
            return [];
        }

        $out = [];
        foreach ($entities as $key => $data) {
            if (!is_array($data)) {
                continue;
            }
            $out[] = [
                'key' => (string) $key,
                'label' => (string) ($data['label'] ?? $key),
                'data' => $data,
            ];
        }

        return $out;
    }

    public static function normalizeRef(?string $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        return substr($v, 0, 64);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function pickPatientRefs(array $data): array
    {
        $out = [];
        foreach (['enabiz_hasta_ref', 'usbs_hasta_ref'] as $key) {
            if (array_key_exists($key, $data)) {
                $out[$key] = self::normalizeRef(is_scalar($data[$key]) ? (string) $data[$key] : null);
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function pickVisitRefs(array $data): array
    {
        $out = [];
        foreach (['usbs_bildirim_ref', 'erecete_ref'] as $key) {
            if (array_key_exists($key, $data)) {
                $out[$key] = self::normalizeRef(is_scalar($data[$key]) ? (string) $data[$key] : null);
            }
        }
        if (array_key_exists('usbs_bildirim_durum', $data)) {
            $out['usbs_bildirim_durum'] = self::normalizeBildirimDurum(
                is_scalar($data['usbs_bildirim_durum']) ? (string) $data['usbs_bildirim_durum'] : ''
            );
        }

        return $out;
    }

    public static function normalizeBildirimDurum(?string $value): string
    {
        $v = strtolower(trim((string) $value));
        $allowed = ['pending', 'sent', 'failed', 'skipped'];
        if (!in_array($v, $allowed, true)) {
            return '';
        }

        return $v;
    }

    /**
     * @return array<string, string>
     */
    public static function patientRefLabels(): array
    {
        return [
            'enabiz_hasta_ref' => 'e-Nabız hasta no',
            'usbs_hasta_ref' => 'USBS hasta no',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function visitRefLabels(): array
    {
        return [
            'usbs_bildirim_ref' => 'USBS bildirim no',
            'erecete_ref' => 'e-Reçete no',
            'usbs_bildirim_durum' => 'Bildirim durumu',
        ];
    }

    public static function bildirimDurumLabel(?string $code): string
    {
        $map = [
            'pending' => 'Bekliyor',
            'sent' => 'Gönderildi',
            'failed' => 'Başarısız',
            'skipped' => 'Atlandı',
        ];
        $code = strtolower(trim((string) $code));

        return $map[$code] ?? '—';
    }

    /**
     * @return array{
     *   patients_missing: int,
     *   bildirim_pending: int,
     *   bildirim_sent: int,
     *   bildirim_failed: int,
     *   bildirim_skipped: int,
     *   visits_missing_ref: int,
     *   kurum_id: ?int
     * }
     */
    public static function complianceKpis(?int $kurumId = null): array
    {
        if (!self::columnsReady()) {
            return [
                'patients_missing' => 0,
                'bildirim_pending' => 0,
                'bildirim_sent' => 0,
                'bildirim_failed' => 0,
                'bildirim_skipped' => 0,
                'visits_missing_ref' => 0,
                'kurum_id' => $kurumId,
            ];
        }
        try {
            $db = \App\Core\Database::getInstance();
            $params = [];
            $kurumSql = '';
            if ($kurumId !== null && $kurumId > 0) {
                $kurumSql = ' AND kurum_id = ?';
                $params[] = $kurumId;
            }
            $pMissing = (int) $db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__hastalar WHERE pasif = 0'
                    . ' AND (TRIM(COALESCE(enabiz_hasta_ref, \'\')) = \'\' OR TRIM(COALESCE(usbs_hasta_ref, \'\')) = \'\')'
                    . $kurumSql,
                $params
            );
            $pending = (int) $db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__izlemler WHERE yapildimi = 1'
                    . ' AND (TRIM(COALESCE(usbs_bildirim_durum, \'\')) = \'\' OR LOWER(usbs_bildirim_durum) = \'pending\')'
                    . $kurumSql,
                $params
            );
            $sent = (int) $db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__izlemler WHERE yapildimi = 1 AND LOWER(usbs_bildirim_durum) = \'sent\''
                    . $kurumSql,
                $params
            );
            $failed = (int) $db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__izlemler WHERE yapildimi = 1 AND LOWER(usbs_bildirim_durum) IN (\'failed\', \'hata\')'
                    . $kurumSql,
                $params
            );
            $skipped = (int) $db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__izlemler WHERE yapildimi = 1 AND LOWER(usbs_bildirim_durum) = \'skipped\''
                    . $kurumSql,
                $params
            );
            $vMissing = (int) $db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__izlemler WHERE yapildimi = 1'
                    . ' AND TRIM(COALESCE(usbs_bildirim_ref, \'\')) = \'\''
                    . $kurumSql,
                $params
            );

            return [
                'patients_missing' => $pMissing,
                'bildirim_pending' => $pending,
                'bildirim_sent' => $sent,
                'bildirim_failed' => $failed,
                'bildirim_skipped' => $skipped,
                'visits_missing_ref' => $vMissing,
                'kurum_id' => $kurumId,
            ];
        } catch (\Throwable $e) {
            return [
                'patients_missing' => 0,
                'bildirim_pending' => 0,
                'bildirim_sent' => 0,
                'bildirim_failed' => 0,
                'bildirim_skipped' => 0,
                'visits_missing_ref' => 0,
                'kurum_id' => $kurumId,
            ];
        }
    }
}
