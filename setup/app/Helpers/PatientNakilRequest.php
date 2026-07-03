<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Models\HastaNakil;
use App\Models\Kurum;
use App\Models\Patient;

/**
 * Onaylı kurumlar arası hasta nakil talepleri.
 */
final class PatientNakilRequest
{
    public const NAKIL_HEDEF_IL_DISI = 'il_disi';

    public const NAKIL_ACTIVE_ONLY_MSG = 'Nakil işlemleri yalnızca aktif hasta dosyaları için yapılabilir.';

    public static function tableReady(): bool
    {
        return HastaNakil::tableExists();
    }

    public static function hasPending(int $hastaId): bool
    {
        if ($hastaId <= 0 || !self::tableReady()) {
            return false;
        }

        return (new HastaNakil())->findPendingByHastaId($hastaId) !== null;
    }

    public static function countPendingForTargetKurum(int $kurumId): int
    {
        if ($kurumId <= 0 || !self::tableReady()) {
            return 0;
        }

        return (new HastaNakil())->countPendingForTargetKurum($kurumId);
    }

    public static function findPendingForPatient(int $hastaId): ?object
    {
        if ($hastaId <= 0 || !self::tableReady()) {
            return null;
        }

        return (new HastaNakil())->findPendingByHastaId($hastaId);
    }

    public static function getReturnTargetKurumId(object $patient): ?int
    {
        if (!self::tableReady()) {
            return null;
        }
        $hastaId = (int) ($patient->id ?? 0);
        if ($hastaId <= 0) {
            return null;
        }
        $inbound = (new HastaNakil())->findApprovedInboundByHedefHastaId($hastaId);
        if ($inbound === null) {
            return null;
        }
        $kid = (int) ($inbound->kaynak_kurum_id ?? 0);

        return $kid > 0 ? $kid : null;
    }

    /**
     * @return array{onceki_nakil_id: int, hedef_kurum_id: int}|null
     */
    public static function detectReturnNakilContext(object $patient, int $hedefKurumId): ?array
    {
        if ($hedefKurumId <= 0 || !self::tableReady()) {
            return null;
        }

        $hastaId = (int) ($patient->id ?? 0);
        if ($hastaId <= 0) {
            return null;
        }

        $inbound = (new HastaNakil())->findApprovedInboundByHedefHastaId($hastaId);
        if ($inbound === null) {
            return null;
        }

        $originalKurumId = (int) ($inbound->kaynak_kurum_id ?? 0);
        if ($originalKurumId !== $hedefKurumId) {
            return null;
        }

        $oncekiNakilId = (int) ($inbound->id ?? 0);
        if ($oncekiNakilId <= 0) {
            return null;
        }

        return [
            'onceki_nakil_id' => $oncekiNakilId,
            'hedef_kurum_id' => $hedefKurumId,
        ];
    }

    public static function validateGeriNakilHedef(object $patient, int $hedefKurumId, bool $requireActive = true): ?string
    {
        $ctx = self::detectReturnNakilContext($patient, $hedefKurumId);
        if ($ctx === null) {
            return 'Geri nakil için geçerli giden nakil kaydı bulunamadı.';
        }

        if ($requireActive) {
            $activeErr = self::validatePatientEligibleForNakilInit($patient);
            if ($activeErr !== null) {
                return $activeErr;
            }
        }

        $tc = trim((string) ($patient->tckimlik ?? ''));
        if ($tc === '') {
            return 'TC kimlik numarası olmayan hasta nakil edilemez.';
        }

        $currentKid = (int) ($patient->kurum_id ?? 0);
        if ($currentKid === $hedefKurumId) {
            return 'Hasta zaten hedef kurumda kayıtlı.';
        }

        if ($requireActive) {
            $hastaId = (int) ($patient->id ?? 0);
            if ($hastaId > 0 && self::hasPending($hastaId)) {
                return 'Bu hasta için bekleyen nakil talebi var.';
            }
        }

        return null;
    }

    public static function isPatientEligibleForNakilInit(object $patient): bool
    {
        return (string) ($patient->pasif ?? '') === '0';
    }

    public static function validatePatientEligibleForNakilInit(object $patient): ?string
    {
        return self::isPatientEligibleForNakilInit($patient) ? null : self::NAKIL_ACTIVE_ONLY_MSG;
    }

    public static function validateNakilHedef(string $nakilHedef, object $patient, bool $requireActive = true): ?string
    {
        $nakilHedef = trim($nakilHedef);
        if ($nakilHedef === '') {
            return 'Başka kuruma nakil için hedef seçin (il dışı veya kurum).';
        }

        if ($requireActive) {
            $activeErr = self::validatePatientEligibleForNakilInit($patient);
            if ($activeErr !== null) {
                return $activeErr;
            }
        }

        $tc = trim((string) ($patient->tckimlik ?? ''));
        if ($tc === '') {
            return 'TC kimlik numarası olmayan hasta nakil edilemez.';
        }

        if ($nakilHedef === self::NAKIL_HEDEF_IL_DISI) {
            return null;
        }

        if (!ctype_digit($nakilHedef)) {
            return 'Geçersiz nakil hedefi.';
        }

        $hedefKurumId = (int) $nakilHedef;
        if (self::detectReturnNakilContext($patient, $hedefKurumId) !== null) {
            return self::validateGeriNakilHedef($patient, $hedefKurumId, $requireActive);
        }

        if ($requireActive) {
            return PatientKurumTransfer::validateTargetKurum($patient, $hedefKurumId);
        }

        return PatientKurumTransfer::validateTargetKurumForApprove($patient, $hedefKurumId);
    }

    public static function validatePasifNedeniForStore(int $pasifNedeni, bool $isAdmin): ?string
    {
        if ($pasifNedeni === PatientKurumTransfer::PASIF_NEDENI_NAKIL && !$isAdmin) {
            return 'Başka kuruma nakil nedeni yalnızca yönetici tarafından seçilebilir.';
        }

        return null;
    }

    /**
     * @return int|false Log id veya false
     */
    public static function createFromPassiveSave(object $patient, string $nakilHedef, int $userId): int|false
    {
        if (!self::tableReady()) {
            return false;
        }

        $hastaId = (int) ($patient->id ?? 0);
        if ($hastaId <= 0 || $userId <= 0) {
            return false;
        }

        if (self::hasPending($hastaId)) {
            return false;
        }

        $err = self::validateNakilHedef($nakilHedef, $patient, false);
        if ($err !== null) {
            return false;
        }

        $kaynakKurumId = (int) ($patient->kurum_id ?? 0);
        if ($kaynakKurumId <= 0) {
            return false;
        }

        $isIlDisi = trim($nakilHedef) === self::NAKIL_HEDEF_IL_DISI;
        $geriCtx = !$isIlDisi && ctype_digit(trim($nakilHedef))
            ? self::detectReturnNakilContext($patient, (int) $nakilHedef)
            : null;

        $nakil = new HastaNakil();
        $bind = [
            'kaynak_hasta_id' => $hastaId,
            'kaynak_kurum_id' => $kaynakKurumId,
            'hedef_kurum_id' => $isIlDisi ? null : (int) $nakilHedef,
            'hedef_hasta_id' => null,
            'onceki_nakil_id' => null,
            'orijinal_kaynak_hasta_id' => null,
            'tip' => $isIlDisi ? HastaNakil::TIP_IL_DISI : HastaNakil::TIP_KURUM_ICI,
            'durum' => $isIlDisi ? HastaNakil::DURUM_ONAYLANDI : HastaNakil::DURUM_BEKLEMEDE,
            'talep_eden_user_id' => $userId,
            'talep_tarihi' => date('Y-m-d H:i:s'),
            'onaylayan_user_id' => $isIlDisi ? $userId : null,
            'onay_tarihi' => $isIlDisi ? date('Y-m-d H:i:s') : null,
            'red_nedeni' => null,
        ];

        if ($geriCtx !== null) {
            $bind['tip'] = HastaNakil::TIP_GERI_NAKIL;
            $bind['onceki_nakil_id'] = $geriCtx['onceki_nakil_id'];
        }

        $nakil->bind($bind, true);

        if (!$nakil->store()) {
            return false;
        }

        return (int) ($nakil->id ?? 0) > 0 ? (int) $nakil->id : false;
    }

    /**
     * Süper yönetici anlık kurum değiştirme — onaylı kurum içi nakil logu.
     *
     * @return int|false Log id
     */
    public static function logInstantApprovedTransfer(
        int $kaynakHastaId,
        int $kaynakKurumId,
        int $hedefKurumId,
        int $hedefHastaId,
        int $userId
    ): int|false {
        if (!self::tableReady()
            || $kaynakHastaId <= 0
            || $kaynakKurumId <= 0
            || $hedefKurumId <= 0
            || $hedefHastaId <= 0
            || $userId <= 0) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $nakil = new HastaNakil();
        $nakil->bind([
            'kaynak_hasta_id' => $kaynakHastaId,
            'kaynak_kurum_id' => $kaynakKurumId,
            'hedef_kurum_id' => $hedefKurumId,
            'hedef_hasta_id' => $hedefHastaId,
            'onceki_nakil_id' => null,
            'orijinal_kaynak_hasta_id' => null,
            'tip' => HastaNakil::TIP_KURUM_ICI,
            'durum' => HastaNakil::DURUM_ONAYLANDI,
            'talep_eden_user_id' => $userId,
            'talep_tarihi' => $now,
            'onaylayan_user_id' => $userId,
            'onay_tarihi' => $now,
            'red_nedeni' => null,
        ], true);

        if (!$nakil->store()) {
            return false;
        }

        return (int) ($nakil->id ?? 0) > 0 ? (int) $nakil->id : false;
    }

    /**
     * @return int|false Onay sonrası hedef hasta id
     */
    public static function approve(int $nakilId, int $approverUserId): int|false
    {
        if (!self::tableReady() || $nakilId <= 0 || $approverUserId <= 0) {
            return false;
        }

        if (!self::canManageIncomingNakil($nakilId, $approverUserId, 'approve')) {
            return false;
        }

        $nakil = new HastaNakil();
        if (!$nakil->loadPendingById($nakilId)) {
            return false;
        }

        $tip = (string) ($nakil->tip ?? '');
        if ($tip === HastaNakil::TIP_GERI_NAKIL) {
            return self::approveGeriNakil($nakil, $approverUserId, $nakilId);
        }

        if ($tip !== HastaNakil::TIP_KURUM_ICI) {
            return false;
        }

        $hedefKurumId = (int) ($nakil->hedef_kurum_id ?? 0);
        if ($hedefKurumId <= 0) {
            return false;
        }

        $patient = new Patient();
        $kaynakHastaId = (int) ($nakil->kaynak_hasta_id ?? 0);
        if (!$patient->load($kaynakHastaId)) {
            return false;
        }

        $err = PatientKurumTransfer::validateTargetKurumForApprove($patient, $hedefKurumId);
        if ($err !== null) {
            return false;
        }

        $db = Database::getInstance();
        $result = $db->transaction(static function (Database $db) use ($hedefKurumId, $approverUserId, $nakilId, $kaynakHastaId): int|false {
            if (!PatientKurumTransfer::movePatientToKurum($kaynakHastaId, $hedefKurumId)) {
                return false;
            }

            $now = date('Y-m-d H:i:s');
            $ok = $db->updatePrepared(
                '#__hasta_nakil',
                [
                    'durum' => HastaNakil::DURUM_ONAYLANDI,
                    'hedef_hasta_id' => $kaynakHastaId,
                    'onaylayan_user_id' => $approverUserId,
                    'onay_tarihi' => $now,
                ],
                'id = ? AND durum = ?',
                [$nakilId, HastaNakil::DURUM_BEKLEMEDE]
            );

            return $ok ? $kaynakHastaId : false;
        });

        return is_int($result) && $result > 0 ? $result : false;
    }

    /**
     * @return int|false Onay sonrası hasta id (tek satır)
     */
    private static function approveGeriNakil(HastaNakil $nakil, int $approverUserId, int $nakilId): int|false
    {
        $hastaId = (int) ($nakil->kaynak_hasta_id ?? 0);
        $hedefKurumId = (int) ($nakil->hedef_kurum_id ?? 0);
        if ($hastaId <= 0 || $hedefKurumId <= 0) {
            return false;
        }

        $patient = new Patient();
        if (!$patient->load($hastaId)) {
            return false;
        }

        $err = self::validateGeriNakilHedef($patient, $hedefKurumId, false);
        if ($err !== null) {
            return false;
        }

        $db = Database::getInstance();
        $result = $db->transaction(static function (Database $db) use ($approverUserId, $nakilId, $hedefKurumId, $hastaId): int|false {
            if (!PatientKurumTransfer::movePatientToKurum($hastaId, $hedefKurumId)) {
                return false;
            }

            $now = date('Y-m-d H:i:s');
            $ok = $db->updatePrepared(
                '#__hasta_nakil',
                [
                    'durum' => HastaNakil::DURUM_ONAYLANDI,
                    'hedef_hasta_id' => $hastaId,
                    'onaylayan_user_id' => $approverUserId,
                    'onay_tarihi' => $now,
                ],
                'id = ? AND durum = ?',
                [$nakilId, HastaNakil::DURUM_BEKLEMEDE]
            );

            return $ok ? $hastaId : false;
        });

        return is_int($result) && $result > 0 ? $result : false;
    }

    public static function reject(int $nakilId, int $userId, ?string $redNedeni = null): bool
    {
        if (!self::tableReady() || $nakilId <= 0 || $userId <= 0) {
            return false;
        }

        if (!self::canManageIncomingNakil($nakilId, $userId, 'reject')) {
            return false;
        }

        $nakil = new HastaNakil();
        if (!$nakil->loadPendingById($nakilId)) {
            return false;
        }

        $db = Database::getInstance();
        $reason = $redNedeni !== null ? trim($redNedeni) : '';
        if (strlen($reason) > 500) {
            $reason = substr($reason, 0, 500);
        }

        return $db->updatePrepared(
            '#__hasta_nakil',
            [
                'durum' => HastaNakil::DURUM_REDDEDILDI,
                'onaylayan_user_id' => $userId,
                'onay_tarihi' => date('Y-m-d H:i:s'),
                'red_nedeni' => $reason !== '' ? $reason : null,
            ],
            'id = ? AND durum = ?',
            [$nakilId, HastaNakil::DURUM_BEKLEMEDE]
        );
    }

    public static function cancel(int $nakilId, int $userId): bool
    {
        if (!self::tableReady() || $nakilId <= 0 || $userId <= 0) {
            return false;
        }

        if (!self::canCancelNakil($nakilId, $userId)) {
            return false;
        }

        $db = Database::getInstance();

        return $db->updatePrepared(
            '#__hasta_nakil',
            [
                'durum' => HastaNakil::DURUM_IPTAL,
                'onaylayan_user_id' => $userId,
                'onay_tarihi' => date('Y-m-d H:i:s'),
            ],
            'id = ? AND durum = ?',
            [$nakilId, HastaNakil::DURUM_BEKLEMEDE]
        );
    }

    public static function canManageIncomingNakil(int $nakilId, int $userId, string $action): bool
    {
        if (!AuthHelper::sessionIsAdmin() || $nakilId <= 0) {
            return false;
        }

        $nakil = new HastaNakil();
        if (!$nakil->load($nakilId)) {
            return false;
        }

        if ((string) ($nakil->durum ?? '') !== HastaNakil::DURUM_BEKLEMEDE) {
            return false;
        }

        if (AuthHelper::sessionIsSuperAdmin()) {
            return true;
        }

        $hedefKurumId = (int) ($nakil->hedef_kurum_id ?? 0);
        $sessionKurum = TenantContext::sessionKurumId();

        return $action === 'approve' || $action === 'reject'
            ? $sessionKurum !== null && $sessionKurum === $hedefKurumId
            : false;
    }

    public static function canCancelNakil(int $nakilId, int $userId): bool
    {
        if (!AuthHelper::sessionIsAdmin() || $nakilId <= 0) {
            return false;
        }

        $nakil = new HastaNakil();
        if (!$nakil->load($nakilId)) {
            return false;
        }

        if ((string) ($nakil->durum ?? '') !== HastaNakil::DURUM_BEKLEMEDE) {
            return false;
        }

        if (AuthHelper::sessionIsSuperAdmin()) {
            return true;
        }

        $kaynakKurumId = (int) ($nakil->kaynak_kurum_id ?? 0);
        $sessionKurum = TenantContext::sessionKurumId();

        return $sessionKurum !== null && $sessionKurum === $kaynakKurumId;
    }

    /** @return list<object> */
    public static function getIncomingList(?int $kurumId): array
    {
        if (!self::tableReady()) {
            return [];
        }

        $super = AuthHelper::sessionIsSuperAdmin();

        return (new HastaNakil())->getIncomingForTargetKurum($kurumId, $super);
    }

    public static function nakilStatusLabelForPatient(int $hastaId): ?string
    {
        if ($hastaId <= 0) {
            return null;
        }

        $patient = new Patient();
        if (!$patient->load($hastaId)) {
            return null;
        }

        return self::nakilViewSummaryForPatient($patient);
    }

    public static function nakilViewSummaryForPatient(object $patient): ?string
    {
        $hastaId = (int) ($patient->id ?? 0);
        if ($hastaId <= 0) {
            return null;
        }

        if (self::tableReady()) {
            $pending = self::findPendingForPatient($hastaId);
            if ($pending !== null) {
                return self::formatNakilLogSummary($pending, $patient);
            }
        }

        $isPasifNakil = (string) ($patient->pasif ?? '') === '1'
            && (string) ($patient->pasifnedeni ?? '') === (string) PatientKurumTransfer::PASIF_NEDENI_NAKIL;
        if (!$isPasifNakil) {
            return null;
        }

        if (!self::tableReady()) {
            return 'Başka kuruma nakil';
        }

        $log = (new HastaNakil())->findLatestByKaynakHastaId($hastaId);
        if ($log !== null) {
            return self::formatNakilLogSummary($log, $patient);
        }

        return 'Başka kuruma nakil';
    }

    private static function formatNakilLogSummary(object $log, ?object $patient = null): string
    {
        $tip = (string) ($log->tip ?? '');
        $durum = (string) ($log->durum ?? '');

        if ($tip === HastaNakil::TIP_IL_DISI) {
            return 'İl dışına nakil';
        }

        if ($tip === HastaNakil::TIP_GERI_NAKIL) {
            $hedefAd = self::kurumAdById((int) ($log->hedef_kurum_id ?? 0));
            if ($durum === HastaNakil::DURUM_BEKLEMEDE) {
                return $hedefAd !== ''
                    ? 'Geri nakil: ' . $hedefAd . ' (onay bekleniyor)'
                    : 'Geri nakil talebi bekliyor';
            }
            if ($durum === HastaNakil::DURUM_ONAYLANDI) {
                return $hedefAd !== ''
                    ? 'Geri nakil: ' . $hedefAd . ' (onaylandı, bekleyen kayıt)'
                    : 'Geri nakil onaylandı (bekleyen kayıt)';
            }
            if ($durum === HastaNakil::DURUM_REDDEDILDI) {
                return $hedefAd !== ''
                    ? 'Geri nakil: ' . $hedefAd . ' (reddedildi)'
                    : 'Geri nakil reddedildi';
            }
            if ($durum === HastaNakil::DURUM_IPTAL) {
                return 'Geri nakil talebi iptal edildi';
            }
        }

        $hedefAd = self::kurumAdById((int) ($log->hedef_kurum_id ?? 0));

        if ($durum === HastaNakil::DURUM_BEKLEMEDE) {
            return $hedefAd !== ''
                ? 'Hedef kurum: ' . $hedefAd . ' (onay bekleniyor)'
                : 'Nakil talebi bekliyor';
        }
        if ($durum === HastaNakil::DURUM_ONAYLANDI) {
            return $hedefAd !== ''
                ? 'Nakil hedef kurum: ' . $hedefAd . ' (onaylandı, bekleyen kayıt)'
                : 'Kurum içi nakil (onaylandı, bekleyen kayıt)';
        }
        if ($durum === HastaNakil::DURUM_REDDEDILDI) {
            $base = $hedefAd !== ''
                ? 'Hedef kurum: ' . $hedefAd . ' (nakil reddedildi)'
                : 'Nakil reddedildi';
            $red = trim((string) ($log->red_nedeni ?? ''));

            return $red !== '' ? $base . ' — ' . $red : $base;
        }
        if ($durum === HastaNakil::DURUM_IPTAL) {
            return 'Nakil talebi iptal edildi';
        }

        return 'Başka kuruma nakil';
    }

    private static function kurumAdById(int $kurumId): string
    {
        if ($kurumId <= 0) {
            return '';
        }
        $kurum = new Kurum();

        return $kurum->load($kurumId) ? trim((string) ($kurum->ad ?? '')) : '';
    }
}
