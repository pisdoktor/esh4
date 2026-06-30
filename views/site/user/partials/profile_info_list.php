<?php
/**
 * Profil — sol bilgi listesi (TC, ünvan, kurum, iletişim, tarihler).
 *
 * @var \App\Models\User $user
 * @var int|null $daysWithUs
 */
use App\Models\User;

$eshUser = $user ?? null;
if (!$eshUser instanceof User) {
    return;
}

$eshKurumLabel = User::kurumDisplayLabel($eshUser);
?>
<div class="list-group list-group-flush text-start border rounded-3 mx-auto mx-lg-0" style="max-width: 28rem;">
    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
        <span class="text-secondary small"><i class="fa-solid fa-id-card me-2"></i>TC Kimlik No</span>
        <span class="fw-semibold text-dark"><?= !empty($eshUser->tckimlikno) ? \App\Helpers\ValidationHelper::formatTc((string) $eshUser->tckimlikno) : '-' ?></span>
    </div>
    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
        <span class="text-secondary small"><i class="fa-solid fa-user-tag me-2"></i>Ünvan</span>
        <span class="fw-semibold text-dark"><?= htmlspecialchars(User::unvanLabel(isset($eshUser->unvan) ? (string) $eshUser->unvan : null), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
        <span class="text-secondary small"><i class="fa-solid fa-building me-2"></i>Kurum</span>
        <span class="fw-semibold text-dark text-end ps-2"><?= htmlspecialchars($eshKurumLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
        <span class="text-secondary small"><i class="fa-solid fa-envelope me-2"></i>E-Posta</span>
        <span class="fw-semibold text-dark text-break text-end ps-2"><?= htmlspecialchars((string) ($eshUser->email ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
        <span class="text-secondary small"><i class="fa-solid fa-calendar-day me-2"></i>Kayıt Tarihi</span>
        <span class="text-dark small"><?= !empty($eshUser->registerDate) ? date('d-m-Y', strtotime((string) $eshUser->registerDate)) : '—' ?></span>
    </div>
    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
        <span class="text-secondary small"><i class="fa-solid fa-heart me-2"></i>Sistemde geçen süre</span>
        <span class="badge bg-info-subtle text-info-emphasis border"><?= (int) ($daysWithUs ?? 0) ?> gün</span>
    </div>
    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
        <span class="text-secondary small"><i class="fa-solid fa-clock-rotate-left me-2"></i>Son giriş</span>
        <span class="text-muted small text-end ps-2"><?= !empty($eshUser->lastvisit) ? date('d-m-Y H:i', strtotime((string) $eshUser->lastvisit)) : 'İlk giriş' ?></span>
    </div>
    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
        <span class="text-secondary small"><i class="fa-solid fa-palette me-2"></i>Tema tercihi</span>
        <span class="fw-semibold text-dark small text-end"><?= htmlspecialchars(\App\Helpers\ThemeViewHelper::labelForUserUiThemePreference(isset($eshUser->ui_theme) ? (string) $eshUser->ui_theme : null), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
        <span class="text-secondary small"><i class="fa-solid fa-gears me-2"></i>Aktif site teması</span>
        <span class="fw-semibold text-dark small text-end"><?= htmlspecialchars(\App\Helpers\ThemeViewHelper::labelForThemeSlug(\App\Helpers\ThemeViewHelper::siteThemeSlug()), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
</div>
