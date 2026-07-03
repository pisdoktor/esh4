<?php
/**
 * Profil düzenleme — sol özet paneli (avatar + kısa bilgi).
 *
 * @var \App\Models\User $user
 */
$eshUser = $user ?? null;
if (!$eshUser instanceof \App\Models\User) {
    return;
}

use App\Helpers\ThemeViewHelper;
use App\Models\User;

$eshUnvanLabel = User::unvanLabel(isset($eshUser->unvan) ? (string) $eshUser->unvan : null);
$eshThemePref = ThemeViewHelper::labelForUserUiThemePreference(isset($eshUser->ui_theme) ? (string) $eshUser->ui_theme : null);
?>
<aside class="esh-profile-edit-aside" aria-label="Profil özeti">
    <div class="au-panel h-100 esh-profile-edit-aside__card">
        <div class="au-panel__body p-4 text-center text-lg-start">
            <?php
            $eshAvatarWrapClass = 'd-inline-block mb-3 mb-lg-4';
            $eshAvatarContainerClass = 'profile-img-container position-relative d-inline-block mx-auto mx-lg-0';
            $eshAvatarImgClass = 'rounded-circle border border-3 border-white shadow-sm esh-profile-edit-aside__avatar';
            $eshAvatarImgStyle = 'width: 120px; height: 120px; object-fit: cover;';
            $eshAvatarCamClass = 'btn btn-primary btn-sm position-absolute bottom-0 end-0 rounded-circle shadow-sm';
            include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'user/partials/profile_avatar');
            unset($eshAvatarWrapClass, $eshAvatarContainerClass, $eshAvatarImgClass, $eshAvatarImgStyle, $eshAvatarCamClass);
            ?>

            <h2 class="h5 fw-bold mb-1"><?= htmlspecialchars((string) ($eshUser->name ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-muted small mb-3">@<?= htmlspecialchars((string) ($eshUser->username ?? ''), ENT_QUOTES, 'UTF-8') ?></p>

            <ul class="list-unstyled small text-start mb-4 esh-profile-edit-aside__facts">
                <li class="d-flex justify-content-between gap-2 py-2 border-bottom border-light-subtle">
                    <span class="text-secondary"><i class="fa-solid fa-user-tag me-2 opacity-75" aria-hidden="true"></i>Ünvan</span>
                    <span class="fw-semibold text-end"><?= htmlspecialchars($eshUnvanLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li class="d-flex justify-content-between gap-2 py-2 border-bottom border-light-subtle">
                    <span class="text-secondary"><i class="fa-solid fa-palette me-2 opacity-75" aria-hidden="true"></i>Tema</span>
                    <span class="fw-semibold text-end"><?= htmlspecialchars($eshThemePref, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
            </ul>

            <a href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary w-100 rounded-pill btn-sm">
                <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Profile dön
            </a>
        </div>
    </div>
</aside>
