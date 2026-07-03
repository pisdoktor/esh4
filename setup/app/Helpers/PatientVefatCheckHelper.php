<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Patient;

/**
 * Vefat sorgusu yönlendirici — KPS veya belediye mezarlık; hasta güncelleme tek noktada.
 */
final class PatientVefatCheckHelper
{
    private const FALLBACK_STATUSES = ['stub', 'not_configured', 'disabled', 'error'];

    /**
     * Bir sonraki sorguda hangi kaynak kullanılacak (bilgi amaçlı).
     */
    public static function resolveSource(): string
    {
        if (!AppSettings::isModuleEnabled('kps_tc_sorgu') || !OperationalSettings::kpsVefatEnabled()) {
            return 'belediye';
        }

        return 'kps';
    }

    /**
     * Hasta kaydı güncellemeden vefat sorgusu.
     *
     * @return array{
     *   deceased:bool,
     *   olumTarihi:?string,
     *   source:string,
     *   status:string,
     *   message:string
     * }
     */
    public static function queryDeathForPatient(object $row): array
    {
        $useKps = AppSettings::isModuleEnabled('kps_tc_sorgu') && OperationalSettings::kpsVefatEnabled();
        $tc = ValidationHelper::tcDigitsOnly((string) ($row->tckimlik ?? ''));

        if ($useKps && $tc !== '') {
            $kps = KpsTcKimlikClient::checkDeathByTc($tc);
            $status = (string) ($kps['status'] ?? 'error');

            if ($status === 'deceased' && !empty($kps['deceased']) && !empty($kps['olumTarihi'])) {
                return [
                    'deceased' => true,
                    'olumTarihi' => (string) $kps['olumTarihi'],
                    'source' => 'kps',
                    'status' => 'deceased',
                    'message' => (string) ($kps['message'] ?? 'KPS: Vefat tespit edildi.'),
                ];
            }

            if ($status === 'alive') {
                return [
                    'deceased' => false,
                    'olumTarihi' => null,
                    'source' => 'kps',
                    'status' => 'alive',
                    'message' => (string) ($kps['message'] ?? 'KPS: Yaşıyor.'),
                ];
            }

            if (!OperationalSettings::kpsVefatFallbackBelediye() || !in_array($status, self::FALLBACK_STATUSES, true)) {
                return [
                    'deceased' => false,
                    'olumTarihi' => null,
                    'source' => 'kps',
                    'status' => $status,
                    'message' => (string) ($kps['message'] ?? 'KPS vefat sorgusu tamamlanamadı.'),
                ];
            }
        }

        $belediyeDate = BelediyeMezarlikVefatProvider::checkByPatient($row);
        if ($belediyeDate !== null) {
            return [
                'deceased' => true,
                'olumTarihi' => $belediyeDate,
                'source' => 'belediye',
                'status' => 'deceased',
                'message' => 'Belediye mezarlık: Vefat tespit edildi.',
            ];
        }

        return [
            'deceased' => false,
            'olumTarihi' => null,
            'source' => $useKps && OperationalSettings::kpsVefatFallbackBelediye() ? 'belediye' : 'none',
            'status' => 'alive',
            'message' => 'Durum değişikliği yok.',
        ];
    }

    /**
     * TC ile vefat kontrolü; tespit edilirse hasta kaydını günceller.
     *
     * @return array{oldu:int,olumTarihi:?string,mesaj:string,skipped:bool,source?:string,status?:string}
     */
    public static function checkAndApplyByTc(string $tc): array
    {
        $tc = ValidationHelper::tcDigitsOnly(trim($tc));
        if (strlen($tc) !== 11) {
            return ['oldu' => 0, 'olumTarihi' => null, 'mesaj' => 'Geçersiz TC.', 'skipped' => true];
        }

        $model = new Patient();
        $row = $model->findByTc($tc);
        if (!$row) {
            return ['oldu' => 0, 'olumTarihi' => null, 'mesaj' => 'Hasta bulunamadı.', 'skipped' => true];
        }
        if (Patient::isPasifKapali($row->pasif ?? null)) {
            return ['oldu' => 0, 'olumTarihi' => null, 'mesaj' => 'Pasif dosyada MERNİS sorgusu yapılamaz.', 'skipped' => true];
        }

        $query = self::queryDeathForPatient($row);
        if (empty($query['deceased']) || empty($query['olumTarihi'])) {
            return [
                'oldu' => 0,
                'olumTarihi' => null,
                'mesaj' => (string) ($query['message'] ?? 'Durum değişikliği yok.'),
                'skipped' => false,
                'source' => (string) ($query['source'] ?? 'none'),
                'status' => (string) ($query['status'] ?? 'alive'),
            ];
        }

        return self::applyDeathToPatient($row, (string) $query['olumTarihi'], (string) ($query['source'] ?? 'belediye'));
    }

    /**
     * @return array{oldu:int,olumTarihi:?string,mesaj:string,skipped:bool,source:string,status:string}
     */
    public static function applyDeathToPatient(object $row, string $olumTarihiDmY, string $source): array
    {
        $model = new Patient();
        $mesaj = 'Aktif hasta muhtemel vefat olarak işaretlendi.';
        $notePrefix = $source === 'kps' ? 'KPS' : 'Belediye mezarlık';

        if (!$model->load((int) ($row->id ?? 0))) {
            return [
                'oldu' => 0,
                'olumTarihi' => null,
                'mesaj' => 'Hasta kaydı yüklenemedi.',
                'skipped' => true,
                'source' => $source,
                'status' => 'error',
            ];
        }

        $existingNotes = json_decode((string) ($model->notes ?? ''), true);
        if (!is_array($existingNotes)) {
            $existingNotes = [];
        }

        $deathNoteText = $notePrefix . ': Vefat Tespit Edildi (Tarih: ' . $olumTarihiDmY . ')';
        $alreadyNoted = false;
        foreach (array_slice($existingNotes, 0, 5) as $note) {
            if (($note['message'] ?? '') === $deathNoteText) {
                $alreadyNoted = true;
                break;
            }
        }
        if (!$alreadyNoted) {
            array_unshift($existingNotes, [
                'date' => date('d-m-Y H:i'),
                'user' => 'Sistem',
                'message' => $deathNoteText,
            ]);
        }

        $pasiftarihi = date('Y-m-d', strtotime($olumTarihiDmY));
        $oncekiPasif = (int) ($row->pasif ?? 0);
        if ($oncekiPasif === -3) {
            $model->set('pasif', '4');
            $mesaj = 'Bekleyen hasta vefat nedeniyle arafa alındı.';
        } else {
            $model->set('pasif', '-1');
        }
        $model->set('pasifnedeni', '2');
        $model->set('pasiftarihi', $pasiftarihi);
        $model->set('notes', json_encode($existingNotes, JSON_UNESCAPED_UNICODE));
        $model->store();

        return [
            'oldu' => 1,
            'olumTarihi' => $olumTarihiDmY,
            'mesaj' => $mesaj,
            'skipped' => false,
            'source' => $source,
            'status' => 'deceased',
        ];
    }
}
