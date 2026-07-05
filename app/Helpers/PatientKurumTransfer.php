<?php



declare(strict_types=1);



namespace App\Helpers;



use App\Core\Database;
use App\Helpers\IdHelper;

use App\Models\Kurum;

use App\Models\Patient;



/**

 * Kurumlar arası hasta nakil — tek hasta satırı (global TC unique).

 * Onayda yalnızca kurum_id güncellenir; klon oluşturulmaz.

 * İzlem ve planlı izlem kayıtları kaynak kurum_id ile kalır.

 */

final class PatientKurumTransfer

{

    public const PASIF_NEDENI_NAKIL = 9;



    /** @deprecated Nakil bekleyen klon modeli kaldırıldı; gelen nakil logu kullanın. */

    public static function isWaitingFromNakil(object $patient): bool

    {

        if (!PatientNakilRequest::tableReady()) {

            return false;

        }



        $hastaId = IdHelper::normalizeRequestId($patient->id ?? null);

        if ($hastaId === null) {

            return false;

        }



        $kurumId = TenantContext::filterKurumId() ?? TenantContext::sessionKurumId();

        if ($kurumId === null || $kurumId <= 0) {

            return false;

        }



        return (new \App\Models\HastaNakil())->hasPendingInboundForHastaAtKurum($hastaId, $kurumId);

    }



    public static function validate(object $patient, int $newKurumId): ?string

    {

        if (!AuthHelper::sessionIsSuperAdmin()) {

            return 'Bu işlem yalnızca '
                . mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN), 'UTF-8')
                . ' tarafından yapılabilir.';

        }



        $oldKurumId = (int) ($patient->kurum_id ?? 0);

        if ($oldKurumId === (int) $newKurumId) {

            return null;

        }



        return self::validateTargetKurum($patient, $newKurumId);

    }



    public static function validateTargetKurum(object $patient, int $newKurumId): ?string

    {

        if ((string) ($patient->pasif ?? '') !== '0') {

            return PatientNakilRequest::NAKIL_ACTIVE_ONLY_MSG;

        }



        $newKurumId = (int) $newKurumId;

        if ($newKurumId <= 0) {

            return 'Geçerli bir kurum seçin.';

        }



        $kurum = new Kurum();

        if (!$kurum->load($newKurumId)) {

            return 'Seçilen kurum bulunamadı.';

        }

        if (empty($kurum->aktif)) {

            return 'Pasif kuruma hasta taşınamaz.';

        }



        $currentKid = (int) ($patient->kurum_id ?? 0);

        if ($currentKid === $newKurumId) {

            return 'Hedef kurum mevcut kurum ile aynı olamaz.';

        }



        $tc = trim((string) ($patient->tckimlik ?? ''));

        if ($tc === '') {

            return 'TC kimlik numarası olmayan hasta kurumu değiştirilemez.';

        }



        $hastaId = IdHelper::normalizeRequestId($patient->id ?? null);

        if ($hastaId !== null && PatientNakilRequest::hasPending($hastaId)) {

            return 'Bu hasta için bekleyen nakil talebi var.';

        }



        return null;

    }



    /** Onay anı: hasta pasif (nakil bekliyor) olabilir; bekleyen log zaten var. */

    public static function validateTargetKurumForApprove(object $patient, int $newKurumId): ?string

    {

        $newKurumId = (int) $newKurumId;

        if ($newKurumId <= 0) {

            return 'Geçerli bir kurum seçin.';

        }



        $kurum = new Kurum();

        if (!$kurum->load($newKurumId)) {

            return 'Seçilen kurum bulunamadı.';

        }

        if (empty($kurum->aktif)) {

            return 'Pasif kuruma hasta taşınamaz.';

        }



        $currentKid = (int) ($patient->kurum_id ?? 0);

        if ($currentKid === $newKurumId) {

            return 'Hedef kurum mevcut kurum ile aynı olamaz.';

        }



        $tc = trim((string) ($patient->tckimlik ?? ''));

        if ($tc === '') {

            return 'TC kimlik numarası olmayan hasta kurumu değiştirilemez.';

        }



        return null;

    }



    /**

     * Süper yönetici anlık kurum taşıma: kurum_id güncellenir, klon yok.

     *

     * @return int|true|false int = hasta id, true = değişiklik yok, false = hata

     */

    public static function apply(object $patient, int $newKurumId, ?string $actingUserId = null): string|bool

    {

        $err = self::validate($patient, $newKurumId);

        if ($err !== null) {

            return false;

        }



        $oldKurumId = (int) ($patient->kurum_id ?? 0);

        $newKurumId = (int) $newKurumId;

        if ($oldKurumId === $newKurumId) {

            return true;

        }



        $patientId = IdHelper::normalizeRequestId($patient->id ?? null);

        if ($patientId === null || trim((string) ($patient->tckimlik ?? '')) === '') {

            return false;

        }



        $db = Database::getInstance();

        $ok = $db->transaction(static function (Database $db) use ($patientId, $newKurumId): bool {

            return $db->updatePrepared(

                '#__hastalar',

                [

                    'kurum_id' => $newKurumId,

                    'pasif' => '0',

                    'pasifnedeni' => null,

                    'pasiftarihi' => null,

                ],

                'id = ?',

                [$patientId]

            );

        });



        if (!$ok) {

            return false;

        }



        if ($actingUserId !== null && $actingUserId !== '') {

            PatientNakilRequest::logInstantApprovedTransfer(

                $patientId,

                $oldKurumId,

                $newKurumId,

                $patientId,

                $actingUserId

            );

        }



        $patient->kurum_id = $newKurumId;

        $patient->pasif = '0';

        $patient->pasifnedeni = null;

        $patient->pasiftarihi = null;



        return $patientId;

    }



    /**

     * Onaylı nakilde hedef kuruma taşıma (tek satır); hasta bekleyen (pasif=-3) olur.

     */

    public static function movePatientToKurum(string $hastaId, int $newKurumId): bool

    {

        $hastaId = IdHelper::normalizeRequestId($hastaId);

        if ($hastaId === null || $newKurumId <= 0) {

            return false;

        }



        return Database::getInstance()->updatePrepared(

            '#__hastalar',

            [

                'kurum_id' => $newKurumId,

                'pasif' => '-3',

                'pasifnedeni' => null,

                'pasiftarihi' => null,

            ],

            'id = ?',

            [$hastaId]

        );

    }

}


