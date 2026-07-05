<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Ayar paneli kayıt hedefi: platform (PS) → bölge → kurum.
 */
final class SettingsWriteScope
{
    public const TARGET_PLATFORM = 'platform';
    public const TARGET_BOLGE = 'bolge';
    public const TARGET_KURUM = 'kurum';

    /** @var list<string> */
    public const KURUM_SCOPED_TABS = SettingsNavCatalog::KURUM_SCOPED_TABS;

    /** @var list<string> */
    public const PLATFORM_ONLY_TABS = SettingsNavCatalog::PLATFORM_ONLY_TABS;

    public static function canWritePlatformDefaults(): bool
    {
        return AuthHelper::sessionIsPlatformOwner();
    }

    /**
     * @return array{target:string,kurum_id?:int,bolge_id?:int}|string
     */
    public static function resolveSaveTarget(): array|string
    {
        $kurumId = KurumCorporateSettings::writeKurumId();
        if ($kurumId !== null && $kurumId > 0) {
            if (AuthHelper::sessionIsSuperAdmin() && !TenantContext::isKurumInScope($kurumId, false)) {
                return 'Seçili kurum oturum kapsamınızda değil.';
            }

            return ['target' => self::TARGET_KURUM, 'kurum_id' => $kurumId];
        }

        $bolgeId = self::effectiveAdminBolgeId();
        if ($bolgeId !== null && $bolgeId > 0) {
            if (AuthHelper::sessionIsSuperAdminOnly() && !self::superAdminCanAccessBolge($bolgeId)) {
                return 'Bu bölge ayarlarına erişim yetkiniz bulunmamaktadır.';
            }

            return ['target' => self::TARGET_BOLGE, 'bolge_id' => $bolgeId];
        }

        if (AuthHelper::sessionIsSuperAdmin() && !AuthHelper::sessionIsPlatformOwner()) {
            return 'Kurum veya bölge kapsamı seçin. Platform varsayılanları yalnızca '
                . mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER), 'UTF-8')
                . ' tarafından değiştirilebilir.';
        }

        if (!AuthHelper::sessionIsSuperAdmin()) {
            return 'Kurum kapsamı belirlenemedi.';
        }

        return ['target' => self::TARGET_PLATFORM];
    }

    public static function canSaveTab(string $tab): bool
    {
        if ($tab === 'overview') {
            return false;
        }
        if (in_array($tab, self::PLATFORM_ONLY_TABS, true)) {
            return AuthHelper::sessionIsPlatformOwner();
        }
        if (in_array($tab, self::KURUM_SCOPED_TABS, true)) {
            return is_array(self::resolveSaveTarget());
        }

        return false;
    }

    /**
     * Okuma/yazma paneli için efektif bölge (kurum filtresi yokken).
     */
    public static function effectiveAdminBolgeId(): ?int
    {
        if (KurumCorporateSettings::writeKurumId() !== null) {
            return null;
        }
        if (!AuthHelper::sessionIsSuperAdmin()) {
            return null;
        }

        return TenantContext::effectiveBolgeFilterId();
    }

    /**
     * Runtime okuma: oturum kurumu veya panel bölge kapsamı.
     */
    public static function resolveBolgeIdForRead(): ?int
    {
        $kurumId = KurumCorporateSettings::readKurumId();
        if ($kurumId !== null && $kurumId > 0) {
            return BolgeCorporateSettings::bolgeIdForKurum($kurumId);
        }

        return self::effectiveAdminBolgeId();
    }

    /**
     * @return array{mode:string,label:string,hint:string,kurum_id?:int,bolge_id?:int}|null
     */
    public static function adminScopeBanner(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !class_exists(AuthHelper::class, false)) {
            return null;
        }
        if (!AuthHelper::sessionIsAdmin()) {
            return null;
        }

        $kurumId = KurumCorporateSettings::writeKurumId();
        if ($kurumId !== null && $kurumId > 0) {
            $name = KurumCorporateSettings::displayName($kurumId);

            return [
                'mode' => self::TARGET_KURUM,
                'kurum_id' => $kurumId,
                'label' => $name !== '' ? $name : ('Kurum #' . $kurumId),
                'hint' => 'Kayıtlar yalnızca bu kuruma yazılır. Tanımlı olmayan alanlar bölge veya platform varsayılanını kullanır.',
            ];
        }

        $bolgeId = self::effectiveAdminBolgeId();
        if ($bolgeId !== null && $bolgeId > 0) {
            $name = BolgeCorporateSettings::displayName($bolgeId);

            return [
                'mode' => self::TARGET_BOLGE,
                'bolge_id' => $bolgeId,
                'label' => $name !== '' ? $name : ('Bölge #' . $bolgeId),
                'hint' => 'Kayıtlar bu bölgenin varsayılan ayarlarına yazılır. Kurum seçilirse kurum kaydı önceliklidir.',
            ];
        }

        if (AuthHelper::sessionIsPlatformOwner()) {
            return [
                'mode' => self::TARGET_PLATFORM,
                'label' => 'Platform geneli',
                'hint' => 'Kurum veya bölge filtresi seçili değil — değişiklikler tüm kurumlar için platform varsayılanı olur.',
            ];
        }

        if (AuthHelper::sessionIsSuperAdmin()) {
            return [
                'mode' => 'denied',
                'label' => 'Kayıt kapsamı seçilmedi',
                'hint' => 'Üst menüden kurum seçin veya bölge atanmış '
                    . mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN), 'UTF-8')
                    . ' olarak giriş yapın. Platform varsayılanları yalnızca '
                    . mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_PLATFORM_OWNER), 'UTF-8')
                    . ' değiştirebilir.',
            ];
        }

        return null;
    }

    private static function superAdminCanAccessBolge(int $bolgeId): bool
    {
        $assigned = TenantContext::sessionAssignedBolgeId();
        if ($assigned !== null && $assigned > 0) {
            return $assigned === $bolgeId;
        }

        return true;
    }
}
