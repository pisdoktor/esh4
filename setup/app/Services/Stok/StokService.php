<?php
declare(strict_types=1);

namespace App\Services\Stok;

use App\Core\Database;
use App\Helpers\AppSettings;
use App\Helpers\IdHelper;
use App\Helpers\StokHelper;
use App\Models\Kurum;
use App\Models\StokMalzeme;
use App\Services\Sms\SmsPhoneNormalizer;
use App\Services\Sms\SmsProviderFactory;
use App\Services\Sms\SmsService;

/**
 * Stok hareketleri ve bakiye güncelleme.
 */
class StokService
{
    public static function moduleReady(): bool
    {
        return StokMalzeme::tableReady();
    }

    public static function extendTablesReady(): bool
    {
        if (!self::moduleReady()) {
            return false;
        }
        try {
            $db = Database::getInstance();

            return $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$db->replacePrefix('#__stok_uyari_log')]
            ) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getCurrentStock(int $kurumId, int $malzemeId): float
    {
        if ($kurumId < 1 || $malzemeId < 1) {
            return 0.0;
        }
        $db = Database::getInstance();
        $val = $db->loadResultPrepared(
            'SELECT miktar FROM #__stok_mevcut WHERE kurum_id = ? AND malzeme_id = ? LIMIT 1',
            [$kurumId, $malzemeId]
        );
        if ($val === null || $val === false) {
            return 0.0;
        }

        return (float) $val;
    }

    /**
     * @param array{
     *   kurum_id: int,
     *   malzeme_id: int,
     *   hareket_tipi: string,
     *   miktar: float|int|string,
     *   hareket_tarihi: string,
     *   kullanici_id: int,
     *   hasta_id?: int|null,
     *   ekip_id?: int|null,
     *   aciklama?: string|null,
     *   lot_no?: string|null,
     *   skt?: string|null
     * } $data
     * @return array{ok: bool, error?: string, hareket_id?: int}
     */
    public function recordMovement(array $data): array
    {
        if (!self::moduleReady()) {
            return ['ok' => false, 'error' => 'Stok modülü tabloları kurulu değil.'];
        }

        $validated = $this->validateMovementInput($data);
        if (!$validated['ok']) {
            return $validated;
        }
        $v = $validated['data'];

        $db = Database::getInstance();
        $malzemeModel = new StokMalzeme();
        if (!$malzemeModel->loadForKurum($v['malzeme_id'], $v['kurum_id'])) {
            return ['ok' => false, 'error' => 'Malzeme bulunamadı.'];
        }

        try {
            $hareketId = $db->transaction(function () use ($db, $v, $malzemeModel) {
                return $this->applyMovementInTransaction($db, $v, $malzemeModel);
            });
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        } catch (\Throwable) {
            return ['ok' => false, 'error' => 'Stok hareketi kaydedilemedi.'];
        }

        if ($hareketId === false || IdHelper::isEmptyEntityId($hareketId)) {
            return ['ok' => false, 'error' => 'Stok hareketi kaydedilemedi.'];
        }

        return ['ok' => true, 'hareket_id' => (string) $hareketId];
    }

    /**
     * Toplu giriş — tek transaction (ya hepsi ya hiçbiri).
     *
     * @param list<array{malzeme_id: int, miktar: float|int|string, lot_no?: string|null, skt?: string|null}> $lines
     * @param array{kurum_id: int, hareket_tarihi: string, kullanici_id: int, aciklama?: string|null} $common
     * @return array{ok: bool, error?: string, count?: int}
     */
    public function recordBulkGiris(array $lines, array $common): array
    {
        if (!self::moduleReady()) {
            return ['ok' => false, 'error' => 'Stok modülü tabloları kurulu değil.'];
        }

        $kurumId = (int) ($common['kurum_id'] ?? 0);
        $tarih = trim((string) ($common['hareket_tarihi'] ?? ''));
        $kullaniciId = IdHelper::normalizeRequestId($common['kullanici_id'] ?? null);
        $aciklama = isset($common['aciklama']) ? trim((string) $common['aciklama']) : null;
        if ($aciklama === '') {
            $aciklama = null;
        }

        if ($kurumId < 1 || $kullaniciId === null) {
            return ['ok' => false, 'error' => 'Geçersiz kurum veya kullanıcı.'];
        }
        if ($tarih === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tarih)) {
            return ['ok' => false, 'error' => 'Geçerli bir hareket tarihi giriniz.'];
        }

        $prepared = [];
        foreach ($lines as $idx => $line) {
            $malzemeId = (int) ($line['malzeme_id'] ?? 0);
            $miktar = (float) ($line['miktar'] ?? 0);
            if ($malzemeId < 1 || $miktar <= 0) {
                continue;
            }
            $prepared[] = [
                'kurum_id' => $kurumId,
                'malzeme_id' => $malzemeId,
                'hareket_tipi' => 'giris',
                'miktar' => $miktar,
                'hareket_tarihi' => $tarih,
                'kullanici_id' => $kullaniciId,
                'aciklama' => $aciklama,
                'lot_no' => isset($line['lot_no']) ? trim((string) $line['lot_no']) : null,
                'skt' => isset($line['skt']) ? trim((string) $line['skt']) : null,
            ];
        }
        if ($prepared === []) {
            return ['ok' => false, 'error' => 'En az bir geçerli malzeme satırı giriniz.'];
        }

        $db = Database::getInstance();
        $malzemeModel = new StokMalzeme();

        try {
            $count = $db->transaction(function () use ($db, $prepared, $malzemeModel) {
                $n = 0;
                foreach ($prepared as $row) {
                    $validated = $this->validateMovementInput($row);
                    if (!$validated['ok']) {
                        throw new \RuntimeException((string) ($validated['error'] ?? 'Geçersiz satır.'));
                    }
                    if (!$malzemeModel->loadForKurum($row['malzeme_id'], $row['kurum_id'])) {
                        throw new \RuntimeException('Malzeme bulunamadı (#' . $row['malzeme_id'] . ').');
                    }
                    $hid = $this->applyMovementInTransaction($db, $validated['data'], $malzemeModel);
                    if ($hid === false || IdHelper::isEmptyEntityId($hid)) {
                        throw new \RuntimeException('Stok girişi kaydedilemedi.');
                    }
                    ++$n;
                }

                return $n;
            });
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        } catch (\Throwable) {
            return ['ok' => false, 'error' => 'Toplu giriş kaydedilemedi.'];
        }

        return ['ok' => true, 'count' => (int) $count];
    }

    /**
     * Sayım farkı — sistem miktarı ile sayılan miktar arasındaki farkı giriş/çıkış olarak yazar.
     *
     * @param array{kurum_id: int, malzeme_id: int, sayilan_miktar: float|int|string, hareket_tarihi: string, kullanici_id: int} $data
     * @return array{ok: bool, error?: string, skipped?: bool, hareket_id?: int}
     */
    public function recordSayimAdjustment(array $data): array
    {
        $kurumId = (int) ($data['kurum_id'] ?? 0);
        $malzemeId = (int) ($data['malzeme_id'] ?? 0);
        $sayilan = (float) ($data['sayilan_miktar'] ?? 0);
        if ($kurumId < 1 || $malzemeId < 1) {
            return ['ok' => false, 'error' => 'Geçersiz kurum veya malzeme.'];
        }
        if ($sayilan < 0) {
            return ['ok' => false, 'error' => 'Sayılan miktar negatif olamaz.'];
        }

        $current = $this->getCurrentStock($kurumId, $malzemeId);
        $diff = $sayilan - $current;
        if (abs($diff) < 0.0001) {
            return ['ok' => true, 'skipped' => true];
        }

        $tip = $diff > 0 ? 'giris' : 'cikis';

        return $this->recordMovement([
            'kurum_id' => $kurumId,
            'malzeme_id' => $malzemeId,
            'hareket_tipi' => $tip,
            'miktar' => abs($diff),
            'hareket_tarihi' => trim((string) ($data['hareket_tarihi'] ?? date('Y-m-d'))),
            'kullanici_id' => $data['kullanici_id'] ?? null,
            'aciklama' => 'Sayım düzeltmesi',
        ]);
    }

    /**
     * Dashboard yüklemesinde throttle'lı kritik stok SMS (günde 1 / malzeme).
     */
    public function maybeSendCriticalAlerts(int $kurumId): void
    {
        if (
            $kurumId < 1
            || !self::moduleReady()
            || !self::extendTablesReady()
            || !AppSettings::isModuleEnabled('sms_bildirim')
            || !SmsService::isSendConfigured()
        ) {
            return;
        }

        $kurum = new Kurum();
        if (!$kurum->load($kurumId)) {
            return;
        }
        $phone = SmsPhoneNormalizer::normalize((string) ($kurum->telefon ?? ''));
        if ($phone === null) {
            return;
        }

        $items = (new StokMalzeme())->listCriticalItems($kurumId, 20);
        if ($items === []) {
            return;
        }

        $db = Database::getInstance();
        $today = date('Y-m-d');
        $names = [];
        foreach ($items as $item) {
            $mid = (int) ($item->id ?? 0);
            if ($mid < 1) {
                continue;
            }
            $exists = $db->loadResultPrepared(
                'SELECT 1 FROM #__stok_uyari_log WHERE kurum_id = ? AND malzeme_id = ? AND uyari_tarihi = ? LIMIT 1',
                [$kurumId, $mid, $today]
            );
            if ($exists !== null && $exists !== false) {
                continue;
            }
            $names[] = (string) ($item->ad ?? 'Malzeme');
            $db->insertPrepared('#__stok_uyari_log', [
                'kurum_id' => $kurumId,
                'malzeme_id' => $mid,
                'uyari_tarihi' => $today,
                'sms_gonderildi' => 0,
            ]);
        }
        if ($names === []) {
            return;
        }

        $body = 'Kritik stok: ' . implode(', ', array_slice($names, 0, 5));
        if (count($names) > 5) {
            $body .= ' ve ' . (count($names) - 5) . ' kalem daha';
        }
        $body .= '. ESH stok modülünden kontrol ediniz.';

        try {
            $provider = SmsProviderFactory::create();
            $result = $provider->send($phone, $body, ['mesaj_turu' => 'bilgilendirme']);
            if ($result->success) {
                $db->updatePrepared(
                    '#__stok_uyari_log',
                    ['sms_gonderildi' => 1],
                    'kurum_id = ? AND uyari_tarihi = ? AND sms_gonderildi = 0',
                    [$kurumId, $today]
                );
            }
        } catch (\Throwable) {
            // Sessiz — uyarı logu çift SMS'i engeller
        }
    }

    /**
     * Yeni malzeme kaydı sonrası bakiye satırı.
     */
    public function initMalzemeStock(int $kurumId, int $malzemeId): void
    {
        if ($kurumId < 1 || $malzemeId < 1) {
            return;
        }
        (new StokMalzeme())->ensureMevcutRow($kurumId, $malzemeId);
    }

    public function effectiveKurumIdForList(): ?int
    {
        return \App\Helpers\TenantContext::filterKurumId();
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok: bool, error?: string, data?: array<string, mixed>}
     */
    private function validateMovementInput(array $data): array
    {
        $kurumId = (int) ($data['kurum_id'] ?? 0);
        $malzemeId = (int) ($data['malzeme_id'] ?? 0);
        $tip = trim((string) ($data['hareket_tipi'] ?? ''));
        $miktar = (float) ($data['miktar'] ?? 0);
        $tarih = trim((string) ($data['hareket_tarihi'] ?? ''));
        $kullaniciId = IdHelper::normalizeRequestId($data['kullanici_id'] ?? null);

        if ($kurumId < 1 || $malzemeId < 1 || $kullaniciId === null) {
            return ['ok' => false, 'error' => 'Geçersiz kurum, malzeme veya kullanıcı.'];
        }
        if (!array_key_exists($tip, StokHelper::hareketTipiOptions())) {
            return ['ok' => false, 'error' => 'Geçersiz hareket tipi.'];
        }
        if ($miktar <= 0) {
            return ['ok' => false, 'error' => 'Miktar sıfırdan büyük olmalıdır.'];
        }
        if ($tarih === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tarih)) {
            return ['ok' => false, 'error' => 'Geçerli bir hareket tarihi giriniz.'];
        }

        $malzemeModel = new StokMalzeme();
        if (!$malzemeModel->loadForKurum($malzemeId, $kurumId)) {
            return ['ok' => false, 'error' => 'Malzeme bulunamadı.'];
        }
        if (empty($malzemeModel->aktif) && in_array($tip, ['cikis', 'iade'], true)) {
            return ['ok' => false, 'error' => 'Pasif malzeme için bu işlem yapılamaz.'];
        }

        $hastaId = IdHelper::entityIdOrFalse($data['hasta_id'] ?? null);
        $hastaId = $hastaId === false ? null : $hastaId;
        $ekipId = isset($data['ekip_id']) && (int) $data['ekip_id'] > 0 ? (int) $data['ekip_id'] : null;
        $aciklama = isset($data['aciklama']) ? trim((string) $data['aciklama']) : null;
        if ($aciklama === '') {
            $aciklama = null;
        }

        if ($hastaId !== null) {
            $db = Database::getInstance();
            $pRow = $db->fetchObjectPrepared(
                'SELECT kurum_id, pasif FROM #__hastalar WHERE id = ? LIMIT 1',
                [$hastaId]
            );
            if ($pRow === null || (int) ($pRow->kurum_id ?? 0) !== $kurumId) {
                return ['ok' => false, 'error' => 'Seçilen hasta bu kurum kapsamında değil.'];
            }
            if ($tip === 'cikis' && !\App\Models\Patient::isAktif($pRow->pasif ?? null)) {
                return ['ok' => false, 'error' => 'Stok çıkışı yalnızca aktif hastalara yapılabilir.'];
            }
        }

        $lotNo = isset($data['lot_no']) ? trim((string) $data['lot_no']) : null;
        if ($lotNo === '') {
            $lotNo = null;
        }
        $skt = isset($data['skt']) ? trim((string) $data['skt']) : '';
        if ($skt === '') {
            $skt = null;
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $skt)) {
            $skt = \App\Helpers\DateHelper::trDateToYmd($skt) ?? null;
            if ($skt === '') {
                $skt = null;
            }
        }

        return [
            'ok' => true,
            'data' => [
                'kurum_id' => $kurumId,
                'malzeme_id' => $malzemeId,
                'hareket_tipi' => $tip,
                'miktar' => $miktar,
                'hareket_tarihi' => $tarih,
                'kullanici_id' => $kullaniciId,
                'hasta_id' => $hastaId,
                'ekip_id' => $ekipId,
                'aciklama' => $aciklama,
                'lot_no' => $lotNo,
                'skt' => $skt,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $v
     */
    private function applyMovementInTransaction(Database $db, array $v, StokMalzeme $malzemeModel): string|false
    {
        $kurumId = (int) $v['kurum_id'];
        $malzemeId = (int) $v['malzeme_id'];
        $tip = (string) $v['hareket_tipi'];
        $miktar = (float) $v['miktar'];

        $malzemeModel->ensureMevcutRow($kurumId, $malzemeId);

        $current = $this->getCurrentStock($kurumId, $malzemeId);
        $delta = match ($tip) {
            'giris', 'iade' => $miktar,
            'cikis' => -$miktar,
            default => 0.0,
        };
        if ($delta === 0.0) {
            return false;
        }
        $newQty = $current + $delta;
        if ($newQty < -0.0001) {
            throw new \RuntimeException(
                'Yetersiz stok. Mevcut: ' . StokHelper::formatMiktar($current)
                . ', istenen: ' . StokHelper::formatMiktar($miktar)
            );
        }

        $hareketId = IdHelper::generateUuidV4();
        $insertId = $db->insertPrepared('#__stok_hareket', [
            'id' => $hareketId,
            'kurum_id' => $kurumId,
            'malzeme_id' => $malzemeId,
            'hareket_tipi' => $tip,
            'miktar' => $miktar,
            'hareket_tarihi' => $v['hareket_tarihi'],
            'hasta_id' => $v['hasta_id'],
            'ekip_id' => $v['ekip_id'],
            'kullanici_id' => $v['kullanici_id'],
            'aciklama' => $v['aciklama'],
        ]);
        if ($insertId === false) {
            return false;
        }

        $updated = $db->updatePrepared(
            '#__stok_mevcut',
            ['miktar' => max(0, $newQty)],
            'kurum_id = ? AND malzeme_id = ?',
            [$kurumId, $malzemeId]
        );
        if (!$updated) {
            return false;
        }

        if (
            $tip === 'giris'
            && self::extendTablesReady()
            && (!empty($v['lot_no']) || !empty($v['skt']))
        ) {
            $this->upsertPartiRow($db, $kurumId, $malzemeId, $miktar, $v['lot_no'] ?? null, $v['skt'] ?? null);
        }

        return $hareketId;
    }

    private function upsertPartiRow(
        Database $db,
        int $kurumId,
        int $malzemeId,
        float $miktar,
        ?string $lotNo,
        ?string $skt
    ): void {
        $lotNo = $lotNo !== null && $lotNo !== '' ? $lotNo : null;
        $existingId = null;
        if ($lotNo !== null) {
            $existingId = $db->loadResultPrepared(
                'SELECT id FROM #__stok_parti WHERE kurum_id = ? AND malzeme_id = ? AND lot_no = ? LIMIT 1',
                [$kurumId, $malzemeId, $lotNo]
            );
        }
        if ($existingId !== null && $existingId !== false) {
            $db->executePrepared(
                'UPDATE #__stok_parti SET miktar = miktar + ?, skt = COALESCE(?, skt), updated_at = NOW() WHERE id = ?',
                [$miktar, $skt, (int) $existingId]
            );

            return;
        }
        $db->insertPrepared('#__stok_parti', [
            'kurum_id' => $kurumId,
            'malzeme_id' => $malzemeId,
            'lot_no' => $lotNo,
            'skt' => $skt,
            'miktar' => $miktar,
        ]);
    }
}
