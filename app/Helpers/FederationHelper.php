<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Models\FederationRegion;
use App\Models\Kurum;

/**
 * Çok bölgeli SaaS / federasyon hazırlığı.
 */
final class FederationHelper
{
    private const MANIFEST_STATE_FILE = '/storage/cache/federation_manifest_state.json';

    public static function columnsReady(): bool
    {
        return FederationRegion::tableExists() && FederationRegion::columnsReadyOnKurum();
    }

    public static function enabled(): bool
    {
        return self::columnsReady() && OperationalSettings::federationEnabled();
    }

    public static function isReady(): bool
    {
        return self::enabled();
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
     * @return list<int>
     */
    public static function activeKurumIdsForBolge(int $bolgeId): array
    {
        if ($bolgeId < 1 || !self::columnsReady()) {
            return [];
        }
        try {
            $db = Database::getInstance();
            $rows = $db->fetchObjectListPrepared(
                'SELECT id FROM #__kurumlar WHERE aktif = 1 AND bolge_id = ? ORDER BY id ASC',
                [$bolgeId]
            );
        } catch (\Throwable) {
            return [];
        }
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id > 0) {
                $out[] = $id;
            }
        }

        return $out;
    }

    /**
     * @return array<string, string> id => label
     */
    public static function bolgeSelectOptions(bool $onlyActive = true): array
    {
        if (!self::columnsReady()) {
            return [];
        }
        $rows = (new FederationRegion())->getList($onlyActive);
        $out = [];
        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $label = (string) ($row->ad ?? '');
            $kod = (string) ($row->kod ?? '');
            if ($kod !== '') {
                $label .= ' (' . $kod . ')';
            }
            $out[(string) $id] = $label;
        }

        return $out;
    }

    /**
     * @return list<array{bolge: object, kurum_count: int, active_patients: int}>
     */
    public static function overviewRows(): array
    {
        if (!self::columnsReady()) {
            return [];
        }
        $regions = (new FederationRegion())->getList(false);
        $counts = (new FederationRegion())->kurumCountsByBolge();
        $db = Database::getInstance();
        $out = [];
        foreach ($regions as $bolge) {
            $bid = (int) ($bolge->id ?? 0);
            $patientCount = 0;
            if ($bid > 0) {
                try {
                    $patientCount = (int) $db->loadResultPrepared(
                        'SELECT COUNT(*) FROM #__hastalar h
                         INNER JOIN #__kurumlar k ON k.id = h.kurum_id
                         WHERE h.pasif = ? AND k.bolge_id = ?',
                        ['0', $bid]
                    );
                } catch (\Throwable) {
                    $patientCount = 0;
                }
            }
            $out[] = [
                'bolge' => $bolge,
                'kurum_count' => (int) ($counts[$bid] ?? 0),
                'active_patients' => $patientCount,
            ];
        }

        return $out;
    }

    public static function kurumBolgeLabel(?int $bolgeId): string
    {
        if ($bolgeId === null || $bolgeId < 1 || !FederationRegion::tableExists()) {
            return '—';
        }
        $model = new FederationRegion();
        if (!$model->load($bolgeId)) {
            return '—';
        }

        return (string) ($model->ad ?? '—');
    }

    /**
     * @return array{ok:bool,checked_at:string,url:string,data?:array<string,mixed>,error?:string}
     */
    public static function fetchCentralManifest(): array
    {
        $url = OperationalSettings::federationCentralManifestUrl();
        $checkedAt = date('c');
        if ($url === '') {
            return ['ok' => false, 'checked_at' => $checkedAt, 'url' => '', 'error' => 'Manifest URL tanımlı değil.'];
        }
        $ctx = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 8, 'header' => "Accept: application/json\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if (!is_string($raw) || trim($raw) === '') {
            self::saveManifestState(['ok' => false, 'checked_at' => $checkedAt, 'url' => $url, 'error' => 'Manifest alınamadı.']);
            return ['ok' => false, 'checked_at' => $checkedAt, 'url' => $url, 'error' => 'Manifest alınamadı.'];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            self::saveManifestState(['ok' => false, 'checked_at' => $checkedAt, 'url' => $url, 'error' => 'Manifest JSON değil.']);
            return ['ok' => false, 'checked_at' => $checkedAt, 'url' => $url, 'error' => 'Manifest JSON değil.'];
        }
        $state = ['ok' => true, 'checked_at' => $checkedAt, 'url' => $url, 'data' => $decoded];
        self::saveManifestState($state);

        return $state;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function lastManifestState(): ?array
    {
        $path = ROOT_PATH . self::MANIFEST_STATE_FILE;
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return list<array{bolge:string,kurum_count:int,active_patients:int}>
     */
    public static function regionComplianceRows(): array
    {
        $rows = [];
        foreach (self::overviewRows() as $row) {
            $bolgeObj = $row['bolge'] ?? null;
            $rows[] = [
                'bolge' => is_object($bolgeObj) ? (string) ($bolgeObj->ad ?? '—') : '—',
                'kurum_count' => (int) ($row['kurum_count'] ?? 0),
                'active_patients' => (int) ($row['active_patients'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $state
     */
    private static function saveManifestState(array $state): void
    {
        $path = ROOT_PATH . self::MANIFEST_STATE_FILE;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($path, (string) json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
