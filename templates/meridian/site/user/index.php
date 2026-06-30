<article class="mr-page mr-page--user-profile container py-5 esh-page-user-profile" lang="tr">
<header class="visually-hidden"><h1>Profil bilgilerim</h1></header>
    <div class="row justify-content-center">
        <div class="col-12 col-xl-11 col-xxl-10">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i class="fa-solid fa-circle-user me-2"></i>Profil Bilgilerim
                    </h5>
                    <a href="<?= htmlspecialchars(esh_url('User', 'edit'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-user-pen me-1"></i> Düzenle
                    </a>
                </div>

                <div class="card-body py-4 px-3 px-md-4">
                    <div class="row g-4 g-lg-5 align-items-start">
                        <div class="col-lg-5 text-center text-lg-start">
                            <?php include __DIR__ . '/partials/profile_avatar.php'; ?>

                            <h3 class="fw-bold mb-1"><?= htmlspecialchars((string) ($user->name ?? '')) ?></h3>
                            <p class="text-muted mb-2 small">@<?= htmlspecialchars((string) ($user->username ?? '')) ?></p>
                            <div class="mb-4">
                                <?php include ROOT_PATH . '/views/site/user/partials/profile_role_badge.php'; ?>
                            </div>

                            <?php include ROOT_PATH . '/views/site/user/partials/profile_info_list.php'; ?>
                        </div>
                        <div class="col-lg-7">
                            <div id="esh-user-profile-stats-content"
                                 data-esh-fetch-url="<?= htmlspecialchars((string) ($profileStatsFetchUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <aside class="esh-profile-stats mt-4 mt-lg-0">
                                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-body-tertiary">
                                        <div class="card-body p-3 p-lg-4">
                                            <div class="py-5 text-center text-muted">
                                                <span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>
                                                İş özeti yükleniyor...
                                            </div>
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer bg-light border-0 py-3 text-center">
                    <small class="text-muted italic">Hesap bilgileriniz güvenli bir şekilde saklanmaktadır.</small>
                </div>
            </div>
        </div>
    </div>
</article>