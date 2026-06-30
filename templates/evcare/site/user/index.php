<article class="ev-profile mr-page mr-page--user-profile esh-page-user-profile container py-4 py-lg-5" lang="tr">
    <div class="row justify-content-center g-4">
        <div class="col-12 col-xl-11 col-xxl-10">
            <header class="ev-profile-welcome mb-4">
                <p class="ev-profile-welcome__eyebrow mb-1">Hesabım</p>
                <h1 class="ev-profile-welcome__title mb-0">Profil ve iş özeti</h1>
            </header>

            <div class="ev-profile-grid">
                <aside class="ev-profile-aside">
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

                        <h2 class="ev-profile-name h4 fw-bold mb-1"><?= htmlspecialchars((string) ($user->name ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="ev-profile-handle mb-2">@<?= htmlspecialchars((string) ($user->username ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="mb-4">
                            <?php include ROOT_PATH . '/views/site/user/partials/profile_role_badge.php'; ?>
                        </div>

                        <dl class="ev-profile-facts">
                            <div class="ev-profile-facts__row">
                                <dt><i class="fa-solid fa-id-card me-2 opacity-75" aria-hidden="true"></i>TC</dt>
                                <dd><?= !empty($user->tckimlikno) ? \App\Helpers\ValidationHelper::formatTc((string) $user->tckimlikno) : '—'; ?></dd>
                            </div>
                            <div class="ev-profile-facts__row">
                                <dt><i class="fa-solid fa-user-tag me-2 opacity-75" aria-hidden="true"></i>Ünvan</dt>
                                <dd><?= htmlspecialchars(\App\Models\User::unvanLabel(isset($user->unvan) ? (string) $user->unvan : null), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div class="ev-profile-facts__row">
                                <dt><i class="fa-solid fa-building me-2 opacity-75" aria-hidden="true"></i>Kurum</dt>
                                <dd><?= htmlspecialchars(\App\Models\User::kurumDisplayLabel($user), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div class="ev-profile-facts__row">
                                <dt><i class="fa-solid fa-envelope me-2 opacity-75" aria-hidden="true"></i>E-posta</dt>
                                <dd class="text-break"><?= htmlspecialchars((string) ($user->email ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div class="ev-profile-facts__row">
                                <dt><i class="fa-solid fa-calendar-day me-2 opacity-75" aria-hidden="true"></i>Kayıt</dt>
                                <dd><?= !empty($user->registerDate) ? date('d-m-Y', strtotime((string) $user->registerDate)) : '—'; ?></dd>
                            </div>
                            <div class="ev-profile-facts__row">
                                <dt><i class="fa-solid fa-heart me-2 opacity-75" aria-hidden="true"></i>Sistemde</dt>
                                <dd><span class="badge rounded-pill ev-profile-pill"><?= (int) ($daysWithUs ?? 0) ?> gün</span></dd>
                            </div>
                            <div class="ev-profile-facts__row">
                                <dt><i class="fa-solid fa-clock-rotate-left me-2 opacity-75" aria-hidden="true"></i>Son giriş</dt>
                                <dd class="small"><?= !empty($user->lastvisit) ? date('d-m-Y H:i', strtotime((string) $user->lastvisit)) : 'İlk giriş'; ?></dd>
                            </div>
                            <div class="ev-profile-facts__row">
                                <dt><i class="fa-solid fa-palette me-2 opacity-75" aria-hidden="true"></i>Tema</dt>
                                <dd class="small"><?= htmlspecialchars(\App\Helpers\ThemeViewHelper::labelForUserUiThemePreference(isset($user->ui_theme) ? (string) $user->ui_theme : null), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div class="ev-profile-facts__row">
                                <dt><i class="fa-solid fa-gears me-2 opacity-75" aria-hidden="true"></i>Site teması</dt>
                                <dd class="small"><?= htmlspecialchars(\App\Helpers\ThemeViewHelper::labelForThemeSlug(\App\Helpers\ThemeViewHelper::siteThemeSlug()), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                        </dl>

                        <a href="<?= htmlspecialchars(esh_url('User', 'edit'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold mt-2">
                            <i class="fa-solid fa-user-pen me-2"></i>Bilgileri düzenle
                        </a>
                    </div>
                </aside>

                <section class="ev-profile-main" aria-labelledby="ev-profile-stats-heading">
                    <h2 id="ev-profile-stats-heading" class="visually-hidden">İş özeti ve istatistikler</h2>
                    <div id="esh-user-profile-stats-content"
                         data-esh-fetch-url="<?= htmlspecialchars((string) ($profileStatsFetchUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="py-5 text-center text-muted">
                            <span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>
                            İş özeti yükleniyor...
                        </div>
                    </div>
                </section>
            </div>

            <footer class="ev-profile-footnote text-center small text-muted mt-4">
                Hesap bilgileriniz güvenli şekilde saklanır.
            </footer>
        </div>
    </div>
</article>
