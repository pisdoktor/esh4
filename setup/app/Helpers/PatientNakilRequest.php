<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Models\FederationRegion;
use App\Models\HastaNakil;
use App\Models\Kurum;
use App\Models\Patient;

/**
 * Onaylı kurumlar arası hasta nakil talepleri.
 */
final class PatientNakilRequest
{
    /** @deprecated Eski tek değer; yeni akışta bolge_{id} kullanın. */
    public const NAKIL_HEDEF_IL_DISI = 'il_disi';

    public const NAKIL_HEDEF_BOLGE_PREFIX = 'bolge_';

    public const NAKIL_ACTIVE_ONLY_MSG = 'Nakil işlemleri yalnızca aktif hasta dosyaları için yapılabilir.';

    public static function isBolgeHedef(string $nakilHedef): bool
    {
        return str_starts_with(trim($nakilHedef), self::NAKIL_HEDEF_BOLGE_PREFIX);
    }

    public static function parseBolgeHedef(string $nakilHedef): ?int
    {
        $nakilHedef = trim($nakilHedef);
        if (!self::isBolgeHedef($nakilHedef)) {
            return null;
        }
        $idPart = substr($nakilHedef, strlen(self::NAKIL_HEDEF_BOLGE_PREFIX));
        if ($idPart === '' || !ctype_digit($idPart)) {
            return null;
        }
        $id = (int) $idPart;

        return $id > 0 ? $id : null;
    }

    public static function buildBolgeHedef(int $bolgeId): string
    {
        return self::NAKIL_HEDEF_BOLGE_PREFIX . (int) $bolgeId;
    }

    /** @return list<object> İl dışı nakil hedefi için aktif bölgeler (kaynak kurum bölgesi hariç). */
    public static function bolgeListForNakilTarget(object $patient): array
    {
        if (!FederationHelper::enabled()) {
            return [];
        }
        $sourceBolgeId = self::sourceKurumBolgeId($patient);
        $out = [];
        foreach ((new FederationRegion())->getList(true) as $row) {
            $bid = (int) ($row->id ?? 0);
            if ($bid <= 0) {
                continue;
            }
            if ($sourceBolgeId !== null && $sourceBolgeId > 0 && $bid === $sourceBolgeId) {
                continue;
            }
            $out[] = $row;
        }

        return $out;
    }

    public static function sourceKurumBolgeId(object $patient): ?int
    {
        $kurumId = (int) ($patient->kurum_id ?? 0);
        if ($kurumId <= 0 || !Kurum::tableExists()) {
            return null;
        }
        $kurum = new Kurum();
        if (!$kurum->load($kurumId)) {
            return null;
        }
        $bid = (int) ($kurum->bolge_id ?? 0);

        return $bid > 0 ? $bid : null;
    }

    public static function tableReady(): bool
    {
        return HastaNakil::tableExists();
    }

    private static function patientEntityId(mixed $source): ?string
    {
        if (is_object($source)) {
            return IdHelper::normalizeRequestId($source->id ?? null);
        }

        return IdHelper::normalizeRequestId($source);
    }

    public static function hasPending(int|string $hastaId): bool
    {
        $hastaIdNorm = self::patientEntityId($hastaId);
        if ($hastaIdNorm === null || !self::tableReady()) {
            return false;
        }

        return (new HastaNakil())->findPendingByHastaId($hastaIdNorm) !== null;
    }

    public static function countPendingForTargetKurum(int $kurumId): int
    {
        if ($kurumId <= 0 || !self::tableReady()) {
            return 0;
        }

        return (new HastaNakil())->countPendingForTargetKurum($kurumId);
    }

    public static function findPendingForPatient(int|string $hastaId): ?object
    {
        $hastaIdNorm = self::patientEntityId($hastaId);
        if ($hastaIdNorm === null || !self::tableReady()) {
            return null;
        }

        return (new HastaNakil())->findPendingByHastaId($hastaIdNorm);
    }

    public static function getReturnTargetKurumId(object $patient): ?int
    {
        if (!self::tableReady()) {
            return null;
        }
        $hastaId = self::patientEntityId($patient);
        if ($hastaId === null) {
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
     * @return array{onceki_nakil_id: string, hedef_kurum_id: int}|null
     */
    public static function detectReturnNakilContext(object $patient, int $hedefKurumId): ?array
    {
        if ($hedefKurumId <= 0 || !self::tableReady()) {
            return null;
        }

        $hastaId = self::patientEntityId($patient);
        if ($hastaId === null) {
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

        $oncekiNakilId = IdHelper::normalizeRequestId($inbound->id ?? null);
        if ($oncekiNakilId === null) {
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
            $hastaId = self::patientEntityId($patient);
            if ($hastaId !== null && self::hasPending($hastaId)) {
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
            return 'Başka kuruma nakil için hedef bölge veya hedef kurum seçin.';
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
            return 'İl dışı nakil için hedef bölge seçin.';
        }

        $bolgeId = self::parseBolgeHedef($nakilHedef);
        if ($bolgeId !== null) {
            return self::validateBolgeHedef($patient, $bolgeId, $requireActive);
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

    public static function validateBolgeHedef(object $patient, int $hedefBolgeId, bool $requireActive = true): ?string
    {
        if ($hedefBolgeId <= 0) {
            return 'Geçerli bir hedef bölge seçin.';
        }
        if (!FederationHelper::enabled() || !HastaNakil::hedefBolgeColumnReady()) {
            return 'İl dışı bölge nakli için federasyon modülü kurulu değil.';
        }
        $region = new FederationRegion();
        if (!$region->load($hedefBolgeId) || empty($region->aktif)) {
            return 'Seçilen hedef bölge bulunamadı veya pasif.';
        }
        $sourceBolgeId = self::sourceKurumBolgeId($patient);
        if ($sourceBolgeId !== null && $sourceBolgeId > 0 && $sourceBolgeId === $hedefBolgeId) {
            return 'Hasta zaten bu bölgede kayıtlı; il dışı nakil için farklı bir bölge seçin.';
        }
        if ($requireActive) {
            $hastaId = self::patientEntityId($patient);
            if ($hastaId !== null && self::hasPending($hastaId)) {
                return 'Bu hasta için bekleyen nakil talebi var.';
            }
        }

        return null;
    }

    public static function validatePasifNedeniForStore(int $pasifNedeni, bool $isAdmin): ?string
    {
        if ($pasifNedeni === PatientKurumTransfer::PASIF_NEDENI_NAKIL && !$isAdmin) {
            return 'Başka kuruma nakil nedeni yalnızca yönetici tarafından seçilebilir.';
        }

        return null;
    }

    /**
     * @return string|false Log id veya false
     */
    public static function createFromPassiveSave(object $patient, string $nakilHedef, int|string $userId): string|false
    {
        if (!self::tableReady()) {
            return false;
        }

        $hastaId = self::patientEntityId($patient);
        $userIdNorm = IdHelper::normalizeRequestId($userId);
        if ($hastaId === null || $userIdNorm === null) {
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

        $hedefBolgeId = self::parseBolgeHedef(trim($nakilHedef));
        $isIlDisi = $hedefBolgeId !== null;
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
            'durum' => HastaNakil::DURUM_BEKLEMEDE,
            'talep_eden_user_id' => $userIdNorm,
            'talep_tarihi' => date('Y-m-d H:i:s'),
            'onaylayan_user_id' => null,
            'onay_tarihi' => null,
            'red_nedeni' => null,
        ];

        if ($isIlDisi && HastaNakil::hedefBolgeColumnReady()) {
            $bind['hedef_bolge_id'] = $hedefBolgeId;
        }

        if ($geriCtx !== null) {
            $bind['tip'] = HastaNakil::TIP_GERI_NAKIL;
            $bind['onceki_nakil_id'] = $geriCtx['onceki_nakil_id'];
        }

        $nakil->bind($bind, true);

        if (!$nakil->store()) {
            return false;
        }

        return IdHelper::entityIdOrFalse($nakil->id ?? null);
    }

    /**
     * Süper yönetici anlık kurum değiştirme — onaylı kurum içi nakil logu.
     *
     * @return string|false Log id
     */
    public static function logInstantApprovedTransfer(
        int|string $kaynakHastaId,
        int $kaynakKurumId,
        int $hedefKurumId,
        int|string $hedefHastaId,
        int|string $userId
    ): string|false {
        $kaynakHastaIdNorm = IdHelper::normalizeRequestId($kaynakHastaId);
        $hedefHastaIdNorm = IdHelper::normalizeRequestId($hedefHastaId);
        $userIdNorm = IdHelper::normalizeRequestId($userId);
        if (!self::tableReady()
            || $kaynakHastaIdNorm === null
            || $kaynakKurumId <= 0
            || $hedefKurumId <= 0
            || $hedefHastaIdNorm === null
            || $userIdNorm === null) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $nakil = new HastaNakil();
        $nakil->bind([
            'kaynak_hasta_id' => $kaynakHastaIdNorm,
            'kaynak_kurum_id' => $kaynakKurumId,
            'hedef_kurum_id' => $hedefKurumId,
            'hedef_hasta_id' => $hedefHastaIdNorm,
            'onceki_nakil_id' => null,
            'orijinal_kaynak_hasta_id' => null,
            'tip' => HastaNakil::TIP_KURUM_ICI,
            'durum' => HastaNakil::DURUM_ONAYLANDI,
            'talep_eden_user_id' => $userIdNorm,
            'talep_tarihi' => $now,
            'onaylayan_user_id' => $userIdNorm,
            'onay_tarihi' => $now,
            'red_nedeni' => null,
        ], true);

        if (!$nakil->store()) {
            return false;
        }

        return IdHelper::entityIdOrFalse($nakil->id ?? null);
    }

    /**
     * @return string|false Onay sonrası hedef hasta id
     */
    public static function approve(int|string $nakilId, int|string $approverUserId): string|false
    {
        if (!self::tableReady() || IdHelper::isEmptyEntityId($nakilId) || IdHelper::isEmptyEntityId($approverUserId)) {
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
        $kaynakHastaId = IdHelper::normalizeRequestId($nakil->kaynak_hasta_id ?? null);
        if ($kaynakHastaId === null || !$patient->load($kaynakHastaId)) {
            return false;
        }

        $err = PatientKurumTransfer::validateTargetKurumForApprove($patient, $hedefKurumId);
        if ($err !== null) {
            return false;
        }

        $db = Database::getInstance();
        $result = $db->transaction(static function (Database $db) use ($hedefKurumId, $approverUserId, $nakilId, $kaynakHastaId): string|false {
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

        return IdHelper::normalizeRequestId($result) ?? false;
    }

    /**
     * @return string|false Onay sonrası hasta id (tek satır)
     */
    private static function approveGeriNakil(HastaNakil $nakil, int|string $approverUserId, int|string $nakilId): string|false
    {
        $hastaId = IdHelper::normalizeRequestId($nakil->kaynak_hasta_id ?? null);
        $hedefKurumId = (int) ($nakil->hedef_kurum_id ?? 0);
        if ($hastaId === null || $hedefKurumId <= 0) {
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
        $result = $db->transaction(static function (Database $db) use ($approverUserId, $nakilId, $hedefKurumId, $hastaId): string|false {
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

        return IdHelper::normalizeRequestId($result) ?? false;
    }

    /**
     * İl dışı nakil onayı — hedef kurum seçimi ile bekleyen kayıt açılır.
     *
     * @return string|false Onay sonrası hedef hasta id
     */
    public static function approveIlDisiNakil(int|string $nakilId, int $hedefKurumId, int|string $approverUserId): string|false
    {
        if (!self::tableReady() || IdHelper::isEmptyEntityId($nakilId) || $hedefKurumId <= 0 || IdHelper::isEmptyEntityId($approverUserId)) {
            return false;
        }

        if (!self::canManageIncomingNakil($nakilId, $approverUserId, 'approve')) {
            return false;
        }

        $nakil = new HastaNakil();
        if (!$nakil->loadPendingById($nakilId)) {
            return false;
        }

        if ((string) ($nakil->tip ?? '') !== HastaNakil::TIP_IL_DISI) {
            return false;
        }

        $hedefBolgeId = (int) ($nakil->hedef_bolge_id ?? 0);
        if ($hedefBolgeId <= 0 || !HastaNakil::hedefBolgeColumnReady()) {
            return false;
        }

        if (!self::isKurumInBolge($hedefKurumId, $hedefBolgeId)) {
            return false;
        }

        if (!TenantContext::isKurumInScope($hedefKurumId)) {
            return false;
        }

        $patient = new Patient();
        $kaynakHastaId = IdHelper::normalizeRequestId($nakil->kaynak_hasta_id ?? null);
        if ($kaynakHastaId === null || !$patient->load($kaynakHastaId)) {
            return false;
        }

        $err = PatientKurumTransfer::validateTargetKurumForApprove($patient, $hedefKurumId);
        if ($err !== null) {
            return false;
        }

        $db = Database::getInstance();
        $result = $db->transaction(static function (Database $db) use ($hedefKurumId, $approverUserId, $nakilId, $kaynakHastaId): string|false {
            if (!PatientKurumTransfer::movePatientToKurum($kaynakHastaId, $hedefKurumId)) {
                return false;
            }

            $now = date('Y-m-d H:i:s');
            $ok = $db->updatePrepared(
                '#__hasta_nakil',
                [
                    'durum' => HastaNakil::DURUM_ONAYLANDI,
                    'hedef_kurum_id' => $hedefKurumId,
                    'hedef_hasta_id' => $kaynakHastaId,
                    'onaylayan_user_id' => $approverUserId,
                    'onay_tarihi' => $now,
                ],
                'id = ? AND durum = ?',
                [$nakilId, HastaNakil::DURUM_BEKLEMEDE]
            );

            return $ok ? $kaynakHastaId : false;
        });

        return IdHelper::normalizeRequestId($result) ?? false;
    }

    public static function isKurumInBolge(int $kurumId, int $bolgeId): bool
    {
        if ($kurumId <= 0 || $bolgeId <= 0 || !Kurum::tableExists()) {
            return false;
        }
        $kurum = new Kurum();
        if (!$kurum->load($kurumId)) {
            return false;
        }

        return (int) ($kurum->bolge_id ?? 0) === $bolgeId;
    }

    /** @return list<object> */
    public static function kurumListForIlDisiBolge(int $bolgeId): array
    {
        if ($bolgeId <= 0 || !FederationHelper::columnsReady()) {
            return [];
        }

        return (new Kurum())->getList(true, 'ad ASC', $bolgeId);
    }

    public static function reject(int|string $nakilId, int|string $userId, ?string $redNedeni = null): bool
    {
        if (!self::tableReady() || IdHelper::isEmptyEntityId($nakilId) || IdHelper::isEmptyEntityId($userId)) {
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

    public static function cancel(int|string $nakilId, int|string $userId): bool
    {
        if (!self::tableReady() || IdHelper::isEmptyEntityId($nakilId) || IdHelper::isEmptyEntityId($userId)) {
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

    public static function canManageIncomingNakil(int|string $nakilId, int|string $userId, string $action): bool
    {
        if (!AuthHelper::sessionIsAdmin() || IdHelper::isEmptyEntityId($nakilId)) {
            return false;
        }

        $nakil = new HastaNakil();
        if (!$nakil->load($nakilId)) {
            return false;
        }

        if ((string) ($nakil->durum ?? '') !== HastaNakil::DURUM_BEKLEMEDE) {
            return false;
        }

        $tip = (string) ($nakil->tip ?? '');

        if ($tip === HastaNakil::TIP_IL_DISI) {
            if (!AuthHelper::sessionIsSuperAdmin()) {
                return false;
            }
            $hedefBolgeId = (int) ($nakil->hedef_bolge_id ?? 0);
            if ($hedefBolgeId <= 0) {
                return false;
            }
            $effectiveBolge = TenantContext::effectiveBolgeFilterId();
            if ($effectiveBolge !== null && $effectiveBolge > 0) {
                return $hedefBolgeId === $effectiveBolge;
            }

            return AuthHelper::sessionIsPlatformOwner();
        }

        if (AuthHelper::sessionIsSuperAdmin()) {
            $hedefKurumId = (int) ($nakil->hedef_kurum_id ?? 0);
            if ($hedefKurumId <= 0) {
                return false;
            }

            return TenantContext::isKurumInScope($hedefKurumId);
        }

        $hedefKurumId = (int) ($nakil->hedef_kurum_id ?? 0);
        $sessionKurum = TenantContext::sessionKurumId();

        return $action === 'approve' || $action === 'reject'
            ? $sessionKurum !== null && $sessionKurum === $hedefKurumId
            : false;
    }

    public static function canCancelNakil(int|string $nakilId, int|string $userId): bool
    {
        if (!AuthHelper::sessionIsAdmin() || IdHelper::isEmptyEntityId($nakilId)) {
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
            $kaynakKurumId = (int) ($nakil->kaynak_kurum_id ?? 0);

            return $kaynakKurumId > 0 && TenantContext::isKurumInScope($kaynakKurumId);
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

        $model = new HastaNakil();
        $kurumRows = [];
        $ilDisiRows = [];

        if (AuthHelper::sessionIsSuperAdmin()) {
            $bolgeId = TenantContext::effectiveBolgeFilterId();
            if ($bolgeId !== null && $bolgeId > 0) {
                $scopeKurumIds = FederationHelper::activeKurumIdsForBolge($bolgeId);
                $kurumRows = $model->getIncomingForTargetKurum(null, false, $scopeKurumIds);
                $ilDisiRows = $model->getIncomingIlDisiForBolge($bolgeId, false);
            } elseif (AuthHelper::sessionIsPlatformOwner()) {
                $kurumRows = $model->getIncomingForTargetKurum(null, true);
                $ilDisiRows = $model->getIncomingIlDisiForBolge(null, true);
            }
        } else {
            $kurumRows = $model->getIncomingForTargetKurum($kurumId, false);
        }

        $merged = array_merge($kurumRows, $ilDisiRows);
        usort($merged, static function (object $a, object $b): int {
            $ta = strtotime((string) ($a->talep_tarihi ?? '')) ?: 0;
            $tb = strtotime((string) ($b->talep_tarihi ?? '')) ?: 0;

            return $ta <=> $tb;
        });

        return $merged;
    }

    public static function nakilStatusLabelForPatient(int|string $hastaId): ?string
    {
        $hastaIdNorm = self::patientEntityId($hastaId);
        if ($hastaIdNorm === null) {
            return null;
        }

        $patient = new Patient();
        if (!$patient->load($hastaIdNorm)) {
            return null;
        }

        return self::nakilViewSummaryForPatient($patient);
    }

    public static function nakilViewSummaryForPatient(object $patient): ?string
    {
        $hastaId = self::patientEntityId($patient);
        if ($hastaId === null) {
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
            $bolgeAd = self::bolgeAdById((int) ($log->hedef_bolge_id ?? 0));
            if ($bolgeAd === '' && !empty($log->hedef_bolge_ad)) {
                $bolgeAd = trim((string) $log->hedef_bolge_ad);
            }
            if ($durum === HastaNakil::DURUM_BEKLEMEDE) {
                return $bolgeAd !== ''
                    ? 'Hedef bölge: ' . $bolgeAd . ' (onay bekleniyor)'
                    : 'İl dışı nakil talebi bekliyor';
            }
            if ($durum === HastaNakil::DURUM_ONAYLANDI) {
                $hedefAd = self::kurumAdById((int) ($log->hedef_kurum_id ?? 0));

                return $bolgeAd !== ''
                    ? 'İl dışı nakil: ' . $bolgeAd . ($hedefAd !== '' ? ' → ' . $hedefAd : '') . ' (onaylandı, bekleyen kayıt)'
                    : 'İl dışı nakil onaylandı (bekleyen kayıt)';
            }
            if ($durum === HastaNakil::DURUM_REDDEDILDI) {
                $base = $bolgeAd !== ''
                    ? 'Hedef bölge: ' . $bolgeAd . ' (nakil reddedildi)'
                    : 'İl dışı nakil reddedildi';
                $red = trim((string) ($log->red_nedeni ?? ''));

                return $red !== '' ? $base . ' — ' . $red : $base;
            }
            if ($durum === HastaNakil::DURUM_IPTAL) {
                return 'İl dışı nakil talebi iptal edildi';
            }

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

    private static function bolgeAdById(int $bolgeId): string
    {
        if ($bolgeId <= 0 || !FederationRegion::tableExists()) {
            return '';
        }
        $region = new FederationRegion();

        return $region->load($bolgeId) ? trim((string) ($region->ad ?? '')) : '';
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
