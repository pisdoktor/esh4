<?php
/**
 * Profil düzenleme sayfa iskeleti (Aurora Light).
 *
 * @var \App\Models\User $user
 * @var string|null $eshUiThemePartial
 */
?>
<div class="row justify-content-center g-4">
    <div class="col-12 col-xl-11 col-xxl-10">
        <header class="esh-profile-edit-head mb-2 mb-md-3">
            <a href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="esh-profile-edit-head__back btn btn-sm btn-light border rounded-pill mb-3">
                <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>Profile dön
            </a>
            <p class="esh-profile-edit-head__eyebrow text-uppercase small fw-bold text-primary mb-1">Hesabım</p>
            <h1 class="esh-profile-edit-head__title h3 fw-bold mb-2">Profil bilgilerini düzenle</h1>
            <p class="text-muted mb-0 col-lg-9">Ad, iletişim, ünvan ve arayüz tercihlerinizi güncelleyin. Değişiklikler kaydedildikten sonra profil sayfanıza yönlendirilirsiniz.</p>
        </header>

        <div class="row g-4 align-items-start flex-lg-row-reverse">
            <div class="col-lg-8">
                <?php include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'user/partials/profile_edit_form'); ?>
            </div>
            <div class="col-lg-4">
                <?php include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'user/partials/profile_edit_aside'); ?>
            </div>
        </div>

        <p class="text-center text-muted small mt-4 mb-0">Hesap bilgileriniz şifreli bağlantı üzerinden iletilir ve güvenli saklanır.</p>
    </div>
</div>
