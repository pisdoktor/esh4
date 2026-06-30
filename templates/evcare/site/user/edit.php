<article class="ev-profile mr-page mr-page--user-edit esh-page-user-profile esh-page-user-profile-edit container py-4 py-lg-5" lang="tr">
    <div class="row justify-content-center g-4">
        <div class="col-12 col-xl-11 col-xxl-10">
            <header class="ev-profile-welcome mb-4">
                <a href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-light border rounded-pill mb-3">
                    <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>Profile dön
                </a>
                <p class="ev-profile-welcome__eyebrow mb-1">Hesabım</p>
                <h1 class="ev-profile-welcome__title mb-2">Profil bilgilerini düzenle</h1>
                <p class="text-muted small mb-0 col-lg-9">Ad, iletişim, ünvan ve arayüz tercihlerinizi güncelleyin.</p>
            </header>

            <div class="ev-profile-grid ev-profile-grid--edit">
                <aside class="ev-profile-aside" aria-label="Profil özeti">
                    <div class="ev-profile-card ev-profile-card--identity">
                        <?php
                        $eshAvatarWrapClass = '';
                        $eshAvatarContainerClass = 'ev-profile-portrait position-relative d-inline-block';
                        $eshAvatarImgClass = 'ev-profile-portrait__img';
                        $eshAvatarImgStyle = '';
                        $eshAvatarCamClass = 'btn btn-primary btn-sm ev-profile-portrait__cam rounded-circle shadow position-absolute bottom-0 end-0';
                        include __DIR__ . '/partials/profile_avatar.php';
                        unset($eshAvatarWrapClass, $eshAvatarContainerClass, $eshAvatarImgClass, $eshAvatarImgStyle, $eshAvatarCamClass);
                        ?>

                        <h2 class="ev-profile-name h5 fw-bold mb-1"><?= htmlspecialchars((string) ($user->name ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="ev-profile-handle mb-0">@<?= htmlspecialchars((string) ($user->username ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </aside>

                <section class="ev-profile-main ev-profile-main--edit" aria-label="Profil düzenleme formu">
                    <?php
                    $eshUiThemePartial = ROOT_PATH . '/templates/evcare/partials/ui_theme_select_field.php';
                    include ROOT_PATH . '/views/site/user/partials/profile_edit_form.php';
                    ?>
                </section>
            </div>

            <footer class="ev-profile-footnote text-center small text-muted mt-4">
                Hesap bilgileriniz güvenli şekilde saklanır.
            </footer>
        </div>
    </div>
</article>
