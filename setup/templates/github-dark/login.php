<?php
/**
 * GitHub Dark — oturum açma (`active_theme` / site varsayılanı `github-dark` iken).
 */
$__lt = 'github-dark';
?>
<!DOCTYPE html>
<html lang="tr" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8'); ?> | Oturum aç · GitHub</title>
    <?= esh_version_meta_tag(); ?>
    <?= esh_csrf_meta(); ?>

    <?= \App\Helpers\CdnAssetHelper::minimalAuthLayoutStylesHtml() ?>
<?php \App\Helpers\ThemeViewHelper::renderLoginStylesheetsHtml($__lt); ?>

    <?= esh_csp_script_src_tag(SITEURL . '/public/assets/form-input-uppercase.js', 'defer') ?>
    <?= esh_csp_script_src_tag(SITEURL . '/public/assets/form-submit-guard.js', 'defer') ?>
</head>
<body class="page-login page-login-theme-github-dark theme-meridian theme-github-dark mr-login-page min-vh-100">
    <div class="mr-login-backdrop" aria-hidden="true"></div>

    <main class="mr-login-shell container-xxl position-relative py-4 py-lg-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-11 col-lg-9 col-xl-8">
                <article class="mr-login-card card border-0 shadow-lg overflow-hidden" aria-labelledby="mr-login-doc-title">
                    <h1 class="visually-hidden" id="mr-login-doc-title"><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> — Oturum aç</h1>
                    <div class="row g-0">
                        <aside class="col-lg-5 mr-login-brand d-none d-lg-flex flex-column justify-content-between p-4 p-xl-5 text-white" aria-label="Uygulama markası">
                            <div>
                                <div class="mr-login-brand-icon mb-3" aria-hidden="true">
                                    <i class="fa-brands fa-github"></i>
                                </div>
                                <p class="mr-login-kicker text-uppercase small fw-semibold mb-2">GitHub · Primer</p>
                                <h2 id="mr-login-title" class="h3 fw-bold mb-2 lh-sm">SON <span class="mr-login-highlight">EV</span></h2>
                                <p class="small opacity-90 mb-0 pe-lg-2">Güvenli oturum ile hasta kayıtları, planlar ve saha işlemlerine erişin.</p>
                            </div>
                            <div class="mt-4 pt-lg-3">
                                <span class="badge rounded-pill mr-login-version">v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8'); ?></span>
                                <p class="small opacity-80 mt-3 mb-0"><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </aside>

                        <div class="col-lg-7 mr-login-form-col">
                            <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                                <header class="text-center mb-4 d-lg-none">
                                    <div class="mr-login-brand-icon mr-login-brand-icon--sm d-inline-flex text-white mb-2" aria-hidden="true">
                                        <i class="fa-brands fa-github"></i>
                                    </div>
                                    <p class="h5 fw-bold mb-0 text-white">SON <span class="mr-login-highlight">EV</span></p>
                                    <p class="small opacity-75 mb-0 text-white">Evde Sağlık Hizmetleri</p>
                                </header>

                                <header class="mb-4">
                                    <h2 class="h4 fw-bold mb-1 text-white" id="mr-login-form-heading">Oturum aç</h2>
                                    <p class="small mb-0 text-white opacity-75">Kurumsal kullanıcı adı ve şifre.</p>
                                </header>

                                <?php if (!empty($_SESSION['error'])): ?>
                                    <?php $__err = (string) $_SESSION['error']; unset($_SESSION['error']); ?>
                                    <div class="alert alert-danger d-flex align-items-start gap-2 small mb-4" role="alert">
                                        <i class="fa-solid fa-circle-exclamation mt-1 flex-shrink-0" aria-hidden="true"></i>
                                        <span><?= htmlspecialchars($__err, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                <?php endif; ?>

                                <form action="<?= htmlspecialchars(esh_url('Auth', 'doLogin'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="mr-login-form" autocomplete="on" aria-labelledby="mr-login-form-heading">
                            <?php include ROOT_PATH . '/views/partials/auth_login_csrf.php'; ?>
                                    <div class="mb-3">
                                        <label for="mr-login-user" class="form-label small fw-semibold text-white">Kullanıcı adı</label>
                                        <div class="input-group">
                                            <span class="input-group-text border-end-0" id="mr-login-user-addon"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
                                            <input id="mr-login-user" type="text" name="username" class="form-control border-start-0"
                                                   placeholder="Kullanıcı adınız" required autofocus
                                                   data-preserve-case autocomplete="username"
                                                   aria-describedby="mr-login-user-addon">
                                        </div>
                                    </div>
                                    <div class="mb-1">
                                        <label for="mr-login-pass" class="form-label small fw-semibold text-white">Şifre</label>
                                        <div class="input-group">
                                            <span class="input-group-text border-end-0" id="mr-login-pass-addon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                                            <input id="mr-login-pass" type="password" name="password" class="form-control border-start-0"
                                                   placeholder="••••••••" required autocomplete="current-password"
                                                   aria-describedby="mr-login-pass-addon">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 py-3 mt-4 fw-semibold rounded-3">
                                        Oturumu aç
                                        <i class="fa-solid fa-arrow-right-to-bracket ms-2" aria-hidden="true"></i>
                                    </button>
                                </form>

                                <footer class="mt-4 pt-2">
                                    <p class="small text-center mb-0 text-white opacity-75">Yardım için sistem yöneticinize başvurun.</p>
                                    <?php include ROOT_PATH . '/views/partials/auth_login_extras.php'; ?>
                                </footer>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </main><?php include ROOT_PATH . '/views/partials/auth_login_password_toggle.php'; ?>

</body>
</html>
