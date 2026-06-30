<?php
/**
 * Aurora Light giriş — tam sayfa (views/login_template.php yerine).
 * Çözümleme: ThemeViewHelper::resolveLoginView() → templates/aurora/login.php
 *
 * Değişkenler: $_SESSION['error'] (varsa hata mesajı).
 * Ortak: SITEURL, ROOT_PATH, esh_app_name(), esh_app_version().
 */
?>
<!DOCTYPE html>
<html lang="tr" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8'); ?> | Oturum aç</title>
    <?= esh_version_meta_tag(); ?>
    <?= esh_csrf_meta(); ?>

    <?= \App\Helpers\CdnAssetHelper::minimalAuthLayoutStylesHtml() ?>
<?php \App\Helpers\ThemeViewHelper::renderLoginStylesheetsHtml('aurora'); ?>
    <script defer src="<?= htmlspecialchars(SITEURL . '/public/assets/form-input-uppercase.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script defer src="<?= htmlspecialchars(SITEURL . '/public/assets/form-submit-guard.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script defer src="<?= htmlspecialchars(SITEURL . '/public/assets/login-tabs.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</head>
<body class="page-login page-login-theme-aurora min-vh-100">
    <div class="aurora-login-backdrop" aria-hidden="true"></div>

    <div class="container-xxl aurora-login-shell py-4 py-lg-5 position-relative">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10 col-xxl-8">
                <div class="card border-0 overflow-hidden aurora-login-card">
                    <div class="row g-0">
                        <aside class="col-lg-4 aurora-login-brand-col d-none d-lg-flex flex-column justify-content-between p-4 p-xl-5 text-white">
                            <div>
                                <div class="aurora-login-brand-icon mb-4">
                                    <i class="fa-solid fa-house-medical-flag" aria-hidden="true"></i>
                                </div>
                                <p class="aurora-login-brand-kicker text-uppercase small fw-semibold opacity-90 mb-2 letter-spacing">Evde sağlık</p>
                                <h1 class="h3 fw-bold mb-2 lh-sm">SON <span class="aurora-login-highlight">EV</span></h1>
                                <p class="small opacity-85 mb-0 pe-lg-4">Aurora arayüzü ile yumuşak, aydınlık bir oturum deneyimi. Kimlik doğrulama sonrası panel ve sahadaki güncel araçlar açılır.</p>
                            </div>
                            <div class="mt-5 pt-lg-4">
                                <span class="badge aurora-login-version-pill">v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8'); ?></span>
                                <p class="small opacity-75 mt-3 mb-0"><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </aside>

                        <div class="col-lg-8 aurora-login-form-col">
                            <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                                <div class="d-lg-none text-center mb-4">
                                    <div class="aurora-login-brand-icon aurora-login-brand-icon-inline d-inline-flex text-white mb-2">
                                        <i class="fa-solid fa-house-medical-flag" aria-hidden="true"></i>
                                    </div>
                                    <h2 class="h5 fw-bold text-dark mb-0">SON <span class="text-primary">EV</span></h2>
                                    <p class="small text-muted mb-0">Evde Sağlık Hizmetleri</p>
                                </div>

                                <header class="mb-4">
                                    <h2 class="h4 fw-bold text-dark mb-1">Oturum aç</h2>
                                    <p class="small text-muted mb-0">Normal giriş veya e-imza ile devam edin.</p>
                                </header>

                                <?php if (!empty($_SESSION['error'])): ?>
                                    <?php $__err = (string) $_SESSION['error']; unset($_SESSION['error']); ?>
                                    <div class="alert alert-danger d-flex align-items-start gap-2 small aurora-login-alert" role="alert">
                                        <i class="fa-solid fa-circle-exclamation mt-1 flex-shrink-0" aria-hidden="true"></i>
                                        <span><?= htmlspecialchars($__err, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="aurora-login-tabs" data-login-tabs>
                                    <ul class="nav nav-tabs aurora-login-tabs-nav mb-3" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" type="button" role="tab" id="aurora-login-tab-normal"
                                                    data-login-tab data-login-target="#aurora-login-pane-normal"
                                                    aria-controls="aurora-login-pane-normal" aria-selected="true">
                                                <i class="fa-solid fa-key me-2" aria-hidden="true"></i>Normal giriş
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" type="button" role="tab" id="aurora-login-tab-eimza"
                                                    data-login-tab data-login-target="#aurora-login-pane-eimza"
                                                    aria-controls="aurora-login-pane-eimza" aria-selected="false" tabindex="-1">
                                                <i class="fa-solid fa-certificate me-2" aria-hidden="true"></i>E-imza ile giriş
                                            </button>
                                        </li>
                                    </ul>
                                    <div class="tab-content aurora-login-tabs-content">
                                        <div class="tab-pane fade show active" id="aurora-login-pane-normal" role="tabpanel" aria-labelledby="aurora-login-tab-normal" data-login-pane>
                                            <form action="<?= htmlspecialchars(esh_url('Auth', 'doLogin'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="aurora-login-form" autocomplete="on">
                            <?php include ROOT_PATH . '/views/partials/auth_login_csrf.php'; ?>
                                                <div class="mb-3">
                                                    <label for="aurora-login-user" class="form-label small fw-semibold text-secondary">Kullanıcı adı</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text border-end-0"><i class="fa-solid fa-user text-primary" aria-hidden="true"></i></span>
                                                        <input id="aurora-login-user" type="text" name="username" class="form-control border-start-0 ps-1" placeholder="Kullanıcı adınız" required autofocus data-preserve-case autocomplete="username">
                                                    </div>
                                                </div>
                                                <div class="mb-1">
                                                    <label for="aurora-login-pass" class="form-label small fw-semibold text-secondary">Şifre</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text border-end-0"><i class="fa-solid fa-lock text-primary" aria-hidden="true"></i></span>
                                                        <input id="aurora-login-pass" type="password" name="password" class="form-control border-start-0 ps-1" placeholder="••••••••" required autocomplete="current-password">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100 py-3 mt-4 fw-semibold">
                                                    Oturumu aç
                                                    <i class="fa-solid fa-arrow-right-to-bracket ms-2" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <div class="tab-pane fade aurora-login-eimza" id="aurora-login-pane-eimza" role="tabpanel" aria-labelledby="aurora-login-tab-eimza" data-login-pane>
                                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                                <h3 class="h6 fw-bold text-dark mb-0">
                                                    <i class="fa-solid fa-certificate me-2 text-primary" aria-hidden="true"></i>E-imza ile giriş
                                                </h3>
                                                <span class="badge rounded-pill aurora-login-eimza-badge">Yakında</span>
                                            </div>
                                            <p class="small text-muted mb-3">Bu giriş tipi geçici olarak devre dışı. Aktif edildiğinde flash kontrolü ve PIN ile giriş yapılacak.</p>
                                            <div class="mb-3">
                                                <label for="aurora-login-eimza-tc" class="form-label small fw-semibold text-secondary">TC Kimlik No</label>
                                                <div class="input-group">
                                                    <span class="input-group-text border-end-0"><i class="fa-solid fa-id-card text-primary" aria-hidden="true"></i></span>
                                                    <input id="aurora-login-eimza-tc" type="text" class="form-control border-start-0 ps-1" placeholder="11 haneli TC" disabled autocomplete="off">
                                                </div>
                                            </div>
                                            <div class="mb-1">
                                                <label for="aurora-login-eimza-pin" class="form-label small fw-semibold text-secondary">E-imza PIN</label>
                                                <div class="input-group">
                                                    <span class="input-group-text border-end-0"><i class="fa-solid fa-lock text-primary" aria-hidden="true"></i></span>
                                                    <input id="aurora-login-eimza-pin" type="password" class="form-control border-start-0 ps-1" placeholder="Token PIN" disabled autocomplete="off">
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary w-100 py-3 mt-4 fw-semibold" disabled>
                                                E-imza ile giriş yap
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <p class="small text-muted text-center mt-4 mb-0">Yardım için sistem yöneticinize başvurun.</p>
                                <?php include \App\Helpers\ThemeViewHelper::resolvePartial('auth_login_extras'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><?php include ROOT_PATH . '/views/partials/auth_login_password_toggle.php'; ?>

</body>
</html>
