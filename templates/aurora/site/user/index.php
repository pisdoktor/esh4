<div class="container py-5 esh-page-user-profile au-page-user-profile">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-11 col-xxl-10">
            <div class="au-panel overflow-hidden">
                <div class="au-panel__head au-panel__head--split">
                    <div class="d-flex align-items-center gap-2">
                        <span class="au-icon-chip au-icon-chip--grad"><i class="fa-solid fa-circle-user"></i></span>
                        <h2 class="au-panel__title mb-0">Profil Bilgilerim</h2>
                    </div>
                    <a href="<?= htmlspecialchars(esh_url('User', 'edit'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-user-pen me-1"></i> Düzenle
                    </a>
                </div>

                <div class="au-panel__body py-4 px-3 px-md-4">
                    <div class="row g-4 g-lg-5 align-items-start flex-lg-row-reverse">
                        <div class="col-lg-7">
                            <div id="esh-user-profile-stats-content"
                                 data-esh-fetch-url="<?= htmlspecialchars((string) ($profileStatsFetchUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <aside class="esh-profile-stats mt-4 mt-lg-0">
                                    <div class="au-panel au-panel--stats h-100">
                                        <div class="au-panel__body p-3 p-lg-4">
                                            <div class="py-5 text-center text-muted">
                                                <span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>
                                                İş özeti yükleniyor...
                                            </div>
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        </div>
                        <div class="col-lg-5 text-center text-lg-start">
                            <?php include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'user/partials/profile_avatar'); ?>

                            <h3 class="fw-bold mb-1"><?= htmlspecialchars((string) ($user->name ?? '')) ?></h3>
                            <p class="text-muted mb-2 small">@<?= htmlspecialchars((string) ($user->username ?? '')) ?></p>
                            <div class="mb-4">
                                <?php include ROOT_PATH . '/views/site/user/partials/profile_role_badge.php'; ?>
                            </div>

                            <div class="list-group list-group-flush text-start au-profile-facts rounded-3 mx-auto mx-lg-0" style="max-width: 28rem;">
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-secondary small"><i class="fa-solid fa-id-card me-2"></i>TC Kimlik No</span>
                                    <span class="fw-semibold text-dark"><?= !empty($user->tckimlikno) ? \App\Helpers\ValidationHelper::formatTc((string) $user->tckimlikno) : '-' ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-secondary small"><i class="fa-solid fa-user-tag me-2"></i>Ünvan</span>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars(\App\Models\User::unvanLabel(isset($user->unvan) ? (string) $user->unvan : null), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-secondary small"><i class="fa-solid fa-building me-2"></i>Kurum</span>
                                    <span class="fw-semibold text-dark text-end ps-2"><?= htmlspecialchars(\App\Models\User::kurumDisplayLabel($user), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-secondary small"><i class="fa-solid fa-envelope me-2"></i>E-Posta</span>
                                    <span class="fw-semibold text-dark text-break text-end ps-2"><?= htmlspecialchars((string) ($user->email ?? '')) ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-secondary small"><i class="fa-solid fa-calendar-day me-2"></i>Kayıt Tarihi</span>
                                    <span class="text-dark small"><?= !empty($user->registerDate) ? date('d-m-Y', strtotime((string) $user->registerDate)) : '—' ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-secondary small"><i class="fa-solid fa-heart me-2"></i>Sistemde geçen süre</span>
                                    <span class="badge bg-info-subtle text-info-emphasis border"><?= (int) ($daysWithUs ?? 0) ?> gün</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-secondary small"><i class="fa-solid fa-clock-rotate-left me-2"></i>Son giriş</span>
                                    <span class="text-muted small text-end ps-2"><?= !empty($user->lastvisit) ? date('d-m-Y H:i', strtotime((string) $user->lastvisit)) : 'İlk giriş' ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-secondary small"><i class="fa-solid fa-palette me-2"></i>Tema tercihi</span>
                                    <span class="fw-semibold text-dark small text-end"><?= htmlspecialchars(\App\Helpers\ThemeViewHelper::labelForUserUiThemePreference(isset($user->ui_theme) ? (string) $user->ui_theme : null), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-secondary small"><i class="fa-solid fa-gears me-2"></i>Aktif site teması</span>
                                    <span class="fw-semibold text-dark small text-end"><?= htmlspecialchars(\App\Helpers\ThemeViewHelper::labelForThemeSlug(\App\Helpers\ThemeViewHelper::siteThemeSlug()), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="au-panel__foot text-center">
                    <small class="text-muted italic">Hesap bilgileriniz güvenli bir şekilde saklanmaktadır.</small>
                </div>
            </div>
        </div>
    </div>
</div>
