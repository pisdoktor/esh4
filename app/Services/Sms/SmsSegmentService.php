<?php
declare(strict_types=1);

namespace App\Services\Sms;

use App\Core\Database;
use App\Helpers\IdHelper;
use App\Helpers\TenantSqlHelper;
use App\Helpers\ZamanDilimiHelper;
use App\Models\Pansuman;
use App\Models\Patient;
use App\Models\PlannedVisit;
use App\Models\Stats;

/**
 * Segment → hasta kayıtları (+ segment meta).
 */
final class SmsSegmentService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    public function resolvePatients(string $segment, array $params): array
    {
        return match ($segment) {
            'tek_hasta' => $this->tekHasta($params),
            'coklu_hasta' => $this->cokluHasta($params),
            'gunun_plani' => $this->gununPlani($params),
            'pansuman_bugun' => $this->pansumanBugun($params),
            'pansuman_liste' => $this->pansumanListe($params),
            'sonda_yaklasan' => $this->sondaYaklasan($params),
            'planli_izlem' => $this->planliIzlem($params),
            'ilk_ziyaret' => $this->ilkZiyaret($params),
            'bekleyen_kayit' => $this->bekleyenKayit($params),
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    private function tekHasta(array $params): array
    {
        $id = IdHelper::normalizeRequestId($params['hasta_id'] ?? null);
        if ($id === null) {
            return [];
        }
        $hasta = (new Patient())->getById($id);

        return $hasta ? [['hasta' => $hasta, 'meta' => []]] : [];
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    private function cokluHasta(array $params): array
    {
        $ids = $params['hasta_ids'] ?? [];
        if (!is_array($ids)) {
            return [];
        }
        $out = [];
        $patient = new Patient();
        foreach ($ids as $id) {
            $hid = IdHelper::normalizeRequestId($id);
            if ($hid === null) {
                continue;
            }
            $hasta = $patient->getById($hid);
            if ($hasta) {
                $out[] = ['hasta' => $hasta, 'meta' => []];
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    private function gununPlani(array $params): array
    {
        $date = $this->normalizeDate($params['tarih'] ?? date('Y-m-d'));
        $zamanFilter = (int) ($params['zaman'] ?? 0);
        $pv = new PlannedVisit();
        $plans = $pv->getDailyPlans($date);
        $slots = ['sabah', 'ogle', 'aksam'];
        $byId = [];

        foreach ($slots as $idx => $slotKey) {
            if ($zamanFilter > 0 && ZamanDilimiHelper::fromVardiyaIndex($idx) !== $zamanFilter) {
                continue;
            }
            $zLabel = ZamanDilimiHelper::label(ZamanDilimiHelper::fromVardiyaIndex($idx));
            $slot = $plans[$slotKey] ?? [];
            foreach (['planli', 'ilkziyaret', 'pansuman'] as $group) {
                foreach (($slot[$group] ?? []) as $item) {
                    $hid = IdHelper::normalizeRequestId($item->hastaid ?? null);
                    if ($hid === null) {
                        continue;
                    }
                    $byId[$hid] = [
                        'tarih' => date('d.m.Y', strtotime($date)),
                        'zaman_dilimi' => $zLabel,
                        'islem' => (string) ($item->islem_label ?? 'Ziyaret'),
                        'mahalle' => (string) ($item->mahalle ?? ''),
                    ];
                }
            }
        }
        foreach ($plans['nakiller'] ?? [] as $item) {
            $hid = IdHelper::normalizeRequestId($item->hastaid ?? null);
            if ($hid === null) {
                continue;
            }
            $byId[$hid] = [
                'tarih' => date('d.m.Y', strtotime($date)),
                'zaman_dilimi' => 'Nakil',
                'islem' => (string) ($item->islem_label ?? 'Nakil'),
                'mahalle' => (string) ($item->mahalle ?? ''),
            ];
        }

        return $this->loadPatientsByMeta($byId);
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    private function pansumanBugun(array $params): array
    {
        $day = (int) date('N');
        $pansuman = new Pansuman();
        $rows = $pansuman->getPansumanList('', (string) $day, 5000, 0);
        $byId = [];
        foreach ($rows as $h) {
            $hid = IdHelper::normalizeRequestId($h->id ?? null);
            if ($hid !== null) {
                $byId[$hid] = ['islem' => 'Pansuman', 'tarih' => date('d.m.Y')];
            }
        }

        return $this->loadPatientsByMeta($byId);
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    private function pansumanListe(array $params): array
    {
        $filterDay = trim((string) ($params['gun'] ?? ''));
        $search = trim((string) ($params['arama'] ?? ''));
        $pansuman = new Pansuman();
        $rows = $pansuman->getPansumanList($search, $filterDay, 5000, 0);
        $byId = [];
        foreach ($rows as $h) {
            $hid = IdHelper::normalizeRequestId($h->id ?? null);
            if ($hid !== null) {
                $byId[$hid] = ['islem' => 'Pansuman'];
            }
        }

        return $this->loadPatientsByMeta($byId);
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    private function sondaYaklasan(array $params): array
    {
        $days = max(1, min(90, (int) ($params['gun_araligi'] ?? 7)));
        $from = date('Y-m-d');
        $to = date('Y-m-d', strtotime('+' . $days . ' days'));
        $stats = new Stats();
        $degisimTarih = $stats->sondaDegisimTarihiOrderExpr('h');
        $where = ["h.sonda = 1", "h.pasif = '0'"];
        TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');
        $where[] = "{$degisimTarih} >= " . $this->db->quote($from);
        $where[] = "{$degisimTarih} <= " . $this->db->quote($to);
        $sql = "SELECT h.id AS hid, {$degisimTarih} AS sonda_degisim_tarihi
            FROM #__hastalar h WHERE " . implode(' AND ', $where) . ' LIMIT 5000';
        $rows = $this->db->fetchObjectListPrepared($sql, []);
        $byId = [];
        if (is_array($rows)) {
            foreach ($rows as $h) {
                $hid = IdHelper::normalizeRequestId($h->hid ?? null);
                if ($hid === null) {
                    continue;
                }
                $degisim = (string) ($h->sonda_degisim_tarihi ?? '');
                if ($degisim !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $degisim)) {
                    $degisim = date('d.m.Y', strtotime($degisim));
                }
                $byId[$hid] = ['sonda_tarih' => $degisim, 'islem' => 'Sonda değişimi'];
            }
        }

        return $this->loadPatientsByMeta($byId);
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    private function planliIzlem(array $params): array
    {
        $date = $this->normalizeDate($params['tarih'] ?? date('Y-m-d'));
        $where = ['p.planlanantarih = ?', 'COALESCE(p.durum, 0) = 0', "h.pasif = '0'"];
        $bind = [$date];
        TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');
        $sql = 'SELECT DISTINCT h.id AS hid, p.yapilacak
            FROM #__pizlemler p
            INNER JOIN #__hastalar h ON h.tckimlik = p.hastatckimlik
            WHERE ' . implode(' AND ', $where);
        $list = $this->db->fetchObjectListPrepared($sql, $bind);
        $byId = [];
        if (is_array($list)) {
            foreach ($list as $row) {
                $hid = IdHelper::normalizeRequestId($row->hid ?? null);
                if ($hid !== null) {
                    $byId[$hid] = [
                        'tarih' => date('d.m.Y', strtotime($date)),
                        'islem' => 'Planlı izlem',
                    ];
                }
            }
        }

        return $this->loadPatientsByMeta($byId);
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    private function ilkZiyaret(array $params): array
    {
        $date = $this->normalizeDate($params['tarih'] ?? date('Y-m-d'));
        $where = ["h.pasif = '-3'", 'h.randevutarihi = ?'];
        $bind = [$date];
        TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');
        $list = $this->db->fetchObjectListPrepared(
            'SELECT h.id AS hid, h.zaman FROM #__hastalar h WHERE ' . implode(' AND ', $where),
            $bind
        );
        $byId = [];
        if (is_array($list)) {
            foreach ($list as $row) {
                $hid = IdHelper::normalizeRequestId($row->hid ?? null);
                if ($hid !== null) {
                    $z = ZamanDilimiHelper::label(ZamanDilimiHelper::normalize($row->zaman ?? 1));
                    $byId[$hid] = [
                        'tarih' => date('d.m.Y', strtotime($date)),
                        'zaman_dilimi' => $z,
                        'islem' => 'İlk ziyaret',
                    ];
                }
            }
        }

        return $this->loadPatientsByMeta($byId);
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    private function bekleyenKayit(array $params): array
    {
        $where = ["h.pasif = '-3'"];
        TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');
        $list = $this->db->fetchObjectListPrepared(
            'SELECT h.id AS hid FROM #__hastalar h WHERE ' . implode(' AND ', $where) . ' LIMIT 5000',
            []
        );
        $byId = [];
        if (is_array($list)) {
            foreach ($list as $row) {
                $hid = IdHelper::normalizeRequestId($row->hid ?? null);
                if ($hid !== null) {
                    $byId[$hid] = ['islem' => 'Bekleyen kayıt'];
                }
            }
        }

        return $this->loadPatientsByMeta($byId);
    }

    /**
     * @param array<string, array<string, string>> $byId
     * @return list<array{hasta:object,meta:array<string,string>}>
     */
    private function loadPatientsByMeta(array $byId): array
    {
        if ($byId === []) {
            return [];
        }
        $patient = new Patient();
        $out = [];
        foreach ($byId as $hid => $meta) {
            $hasta = $patient->getById($hid);
            if ($hasta) {
                $out[] = ['hasta' => $hasta, 'meta' => $meta];
            }
        }

        return $out;
    }

    private function normalizeDate(mixed $raw): string
    {
        if (!is_string($raw) || trim($raw) === '') {
            return date('Y-m-d');
        }
        $raw = trim($raw);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        $ts = strtotime(str_replace('.', '-', $raw));

        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}
