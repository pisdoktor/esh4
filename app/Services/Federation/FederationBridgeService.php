<?php

declare(strict_types=1);

namespace App\Services\Federation;

use App\Helpers\AuthHelper;
use App\Helpers\IdHelper;
use App\Core\Database;
use App\Helpers\FederationBridgeHelper;
use App\Helpers\FederationHelper;
use App\Helpers\OperationalSettings;
use App\Helpers\TenantContext;
use App\Models\FederationRegion;
use App\Models\FederationSyncLog;
use App\Models\Kurum;

/**
 * Federasyon dosya köprüsü — düğüm anlık görüntüsü ve hub eşlemesi.
 */
final class FederationBridgeService
{
    /**
     * @return array<string, mixed>
     */
    public function exportBundle(): array
    {
        $db = Database::getInstance();
        $regions = (new FederationRegion())->getList(false);
        $regionsOut = [];
        foreach ($regions as $row) {
            $regionsOut[] = [
                'esh_id' => (int) ($row->id ?? 0),
                'kod' => (string) ($row->kod ?? ''),
                'ad' => (string) ($row->ad ?? ''),
                'il_adi' => (string) ($row->il_adi ?? ''),
                'hub_node_ref' => FederationHelper::normalizeRef((string) ($row->hub_node_ref ?? '')),
                'aktif' => (int) ($row->aktif ?? 0),
                'aciklama' => (string) ($row->aciklama ?? ''),
            ];
        }

        $kurumSql = 'SELECT id, kod, ad, aktif, bolge_id, federation_ref, telefon FROM #__kurumlar WHERE 1=1';
        $scopeIds = TenantContext::filterKurumIds();
        if ($scopeIds !== null) {
            if ($scopeIds === []) {
                $kurumSql .= ' AND 1=0';
            } elseif (count($scopeIds) === 1) {
                $kurumSql .= ' AND id = ' . (int) $scopeIds[0];
            } else {
                $kurumSql .= ' AND id IN (' . implode(',', array_map('intval', $scopeIds)) . ')';
            }
        }
        $kurumSql .= ' ORDER BY id ASC';
        $kurumRows = $db->fetchObjectListPrepared($kurumSql, []);
        if (!is_array($kurumRows)) {
            $kurumRows = [];
        }

        $kurumlarOut = [];
        $statsOut = [];
        $includeStats = OperationalSettings::federationExportIncludeStats();
        foreach ($kurumRows as $row) {
            $kid = (int) ($row->id ?? 0);
            $bolgeId = (int) ($row->bolge_id ?? 0);
            $bolgeKod = '';
            if ($bolgeId > 0) {
                $br = new FederationRegion();
                if ($br->load($bolgeId)) {
                    $bolgeKod = (string) ($br->kod ?? '');
                }
            }
            $kurumlarOut[] = [
                'esh_id' => $kid,
                'kod' => (string) ($row->kod ?? ''),
                'ad' => (string) ($row->ad ?? ''),
                'aktif' => (int) ($row->aktif ?? 0),
                'bolge_id' => $bolgeId > 0 ? $bolgeId : null,
                'bolge_kod' => $bolgeKod,
                'federation_ref' => FederationHelper::normalizeRef((string) ($row->federation_ref ?? '')),
                'telefon' => (string) ($row->telefon ?? ''),
            ];
            if ($includeStats && $kid > 0) {
                $statsOut[] = [
                    'kurum_id' => $kid,
                    'active_patients' => (int) $db->loadResultPrepared(
                        'SELECT COUNT(*) FROM #__hastalar WHERE pasif = ? AND kurum_id = ?',
                        ['0', $kid]
                    ),
                    'visits_last_30d' => (int) $db->loadResultPrepared(
                        'SELECT COUNT(*) FROM #__izlemler WHERE kurum_id = ? AND izlemtarihi >= ?',
                        [$kid, date('Y-m-d', strtotime('-30 days'))]
                    ),
                ];
            }
        }

        return [
            'bundle_version' => FederationBridgeHelper::BUNDLE_VERSION,
            'direction' => 'node_snapshot',
            'generated_at' => date('c'),
            'node' => [
                'ref' => OperationalSettings::federationNodeRef(),
                'app_version' => function_exists('esh_app_version') ? esh_app_version() : (defined('ESH_APP_VERSION') ? ESH_APP_VERSION : ''),
                'site_url' => defined('SITEURL') ? (string) SITEURL : '',
            ],
            'regions' => $regionsOut,
            'kurumlar' => $kurumlarOut,
            'stats' => $statsOut,
            'meta' => [
                'region_count' => count($regionsOut),
                'kurum_count' => count($kurumlarOut),
                'include_stats' => $includeStats,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $bundle
     * @return array{ok:bool,direction?:string,stats:array<string,int>,errors:list<string>}
     */
    public function importBundle(array $bundle): array
    {
        $check = FederationBridgeHelper::validateImportBundle($bundle);
        if (empty($check['ok'])) {
            return [
                'ok' => false,
                'stats' => [],
                'errors' => [(string) ($check['error'] ?? 'Geçersiz paket')],
            ];
        }

        $stats = ['regions_upserted' => 0, 'kurumlar_updated' => 0];
        $errors = [];

        $regions = is_array($bundle['regions'] ?? null) ? $bundle['regions'] : [];
        $kodToId = [];
        foreach ($regions as $item) {
            if (!is_array($item)) {
                continue;
            }
            $extracted = FederationBridgeHelper::extractRegionItem($item);
            if ($extracted['kod'] === '' || $extracted['ad'] === '') {
                $errors[] = 'Bölge satırı eksik kod/ad';
                continue;
            }
            $model = new FederationRegion();
            $existing = $this->findRegionByKod($extracted['kod']);
            if ($existing !== null) {
                $model->bind($existing, false);
            }
            $model->bind([
                'kod' => $extracted['kod'],
                'ad' => $extracted['ad'],
                'il_adi' => $extracted['il_adi'] !== '' ? $extracted['il_adi'] : null,
                'hub_node_ref' => $extracted['hub_node_ref'],
                'aktif' => $extracted['aktif'],
                'aciklama' => $extracted['aciklama'] !== '' ? $extracted['aciklama'] : null,
            ], true);
            if ($model->store()) {
                $stats['regions_upserted']++;
                $kodToId[$extracted['kod']] = (int) ($model->id ?? 0);
                \App\Helpers\FederationAdresBolgeSync::syncFromRegion($model);
            } else {
                $errors[] = 'Bölge kaydedilemedi: ' . $extracted['kod'];
            }
        }

        $kurumlar = is_array($bundle['kurumlar'] ?? null) ? $bundle['kurumlar'] : [];
        foreach ($kurumlar as $item) {
            if (!is_array($item)) {
                continue;
            }
            $extracted = FederationBridgeHelper::extractKurumItem($item);
            $kurum = $this->resolveKurum($extracted);
            if ($kurum === null) {
                $errors[] = 'Kurum bulunamadı: ' . ($extracted['kod'] !== '' ? $extracted['kod'] : (string) $extracted['esh_id']);
                continue;
            }
            $bind = [];
            if ($extracted['federation_ref'] !== null) {
                $bind['federation_ref'] = $extracted['federation_ref'];
            }
            if ($extracted['bolge_kod'] !== '' && isset($kodToId[$extracted['bolge_kod']])) {
                $bind['bolge_id'] = $kodToId[$extracted['bolge_kod']];
            } elseif ($extracted['bolge_kod'] !== '') {
                $existingBolge = $this->findRegionByKod($extracted['bolge_kod']);
                if ($existingBolge !== null) {
                    $bind['bolge_id'] = (int) ($existingBolge->id ?? 0);
                }
            }
            if ($bind === []) {
                continue;
            }
            $kurum->bind($bind, true);
            if ($kurum->store()) {
                $stats['kurumlar_updated']++;
            } else {
                $errors[] = 'Kurum güncellenemedi: ' . (string) ($kurum->kod ?? '');
            }
        }

        return [
            'ok' => true,
            'direction' => 'hub_to_node',
            'stats' => $stats,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string, mixed> $stats
     */
    public function logSync(string $direction, string $status, ?string $fileName, array $stats = [], ?string $error = null): void
    {
        $userId = AuthHelper::sessionUserId();
        (new FederationSyncLog())->record(
            $direction,
            $status,
            IdHelper::isEmptyEntityId($userId) ? null : $userId,
            $fileName,
            $stats,
            $error
        );
    }

    /**
     * @return object|null
     */
    private function findRegionByKod(string $kod): ?object
    {
        $db = Database::getInstance();
        $row = $db->fetchOnePrepared(
            'SELECT * FROM #__federation_regions WHERE kod = ? LIMIT 1',
            [$kod]
        );

        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveKurum(array $item): ?Kurum
    {
        $model = new Kurum();
        $eshId = (int) ($item['esh_id'] ?? 0);
        if ($eshId > 0 && $model->load($eshId)) {
            return $model;
        }
        $kod = (string) ($item['kod'] ?? '');
        if ($kod !== '' && $model->loadByKod($kod)) {
            return $model;
        }

        return null;
    }
}
