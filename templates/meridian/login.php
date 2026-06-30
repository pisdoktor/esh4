<?php
/**
 * Meridian — tam sayfa oturum açma (site varsayılan teması `meridian` iken).
 * `ThemeViewHelper::resolveLoginView()` bu dosyayı seçer.
 */
use App\Helpers\AppSettings;

$__eshEimzaLogin = AppSettings::isModuleEnabled('eimza_login');
$__lt = 'meridian';
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
<?php \App\Helpers\ThemeViewHelper::renderLoginStylesheetsHtml($__lt); ?>

    <script defer src="<?= htmlspecialchars(SITEURL . '/public/assets/form-input-uppercase.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script defer src="<?= htmlspecialchars(SITEURL . '/public/assets/form-submit-guard.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script defer src="<?= htmlspecialchars(SITEURL . '/public/assets/login-tabs.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</head>
<body class="page-login page-login-theme-meridian theme-meridian mr-login-page min-vh-100">
    <div class="mr-login-backdrop" aria-hidden="true"></div>

    <main class="mr-login-shell container-xxl position-relative py-4 py-lg-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-11 col-lg-10 col-xl-10">
                <article class="mr-login-card card border-0 shadow-lg overflow-hidden" aria-labelledby="mr-login-doc-title">
                    <h1 class="visually-hidden" id="mr-login-doc-title"><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> — Oturum aç</h1>
                    <div class="row g-0">
                        <aside class="col-lg-4 mr-login-brand d-none d-lg-flex flex-column justify-content-between p-4 p-xl-5 text-white" aria-label="Uygulama markası">
                            <div>
                                <div class="mr-login-brand-icon mb-3" aria-hidden="true">
                                    <i class="fa-solid fa-house-medical-flag"></i>
                                </div>
                                <p class="mr-login-kicker text-uppercase small fw-semibold mb-2">Evde sağlık</p>
                                <h2 id="mr-login-title" class="h3 fw-bold mb-2 lh-sm">SON <span class="mr-login-highlight">EV</span></h2>
                                <p class="small opacity-90 mb-0 pe-lg-2">Giriş yaparak hasta kayıtları, planlar ve saha işlemlerine erişin.</p>
                            </div>
                            <div class="mt-4 pt-lg-3">
                                <span class="badge rounded-pill mr-login-version">v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8'); ?></span>
                                <p class="small opacity-80 mt-3 mb-0"><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </aside>

                        <div class="col-lg-8 mr-login-form-col">
                            <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                                <header class="text-center mb-4 d-lg-none">
                                    <div class="mr-login-brand-icon mr-login-brand-icon--sm d-inline-flex text-white mb-2" aria-hidden="true">
                                        <i class="fa-solid fa-house-medical-flag"></i>
                                    </div>
                                    <p class="h5 fw-bold mb-0 text-white">SON <span class="mr-login-highlight">EV</span></p>
                                    <p class="small opacity-75 mb-0 text-white">Evde Sağlık Hizmetleri</p>
                                </header>

                                <header class="mb-3 mb-lg-4">
                                    <h2 class="h4 fw-bold mb-1 text-white" id="mr-login-form-heading">Oturum aç</h2>
                                    <p class="small mb-0 text-white opacity-75"><?= $__eshEimzaLogin ? 'Normal giriş veya e-imza ile oturum açın.' : 'Kullanıcı adı ve şifre ile oturum açın.' ?></p>
                                </header>

                                <?php if (!empty($_SESSION['error'])): ?>
                                    <?php $__err = (string) $_SESSION['error']; unset($_SESSION['error']); ?>
                                    <div class="alert alert-danger d-flex align-items-start gap-2 small mb-4" role="alert">
                                        <i class="fa-solid fa-circle-exclamation mt-1 flex-shrink-0" aria-hidden="true"></i>
                                        <span><?= htmlspecialchars($__err, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="mr-login-tabs flex-grow-1" data-login-tabs>
                                    <ul class="nav nav-tabs mr-login-tabs-nav mb-3" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" type="button" role="tab" id="mr-login-tab-normal"
                                                    data-login-tab data-login-target="#mr-login-pane-normal"
                                                    aria-controls="mr-login-pane-normal" aria-selected="true">
                                                <i class="fa-solid fa-key me-2" aria-hidden="true"></i>Normal giriş
                                            </button>
                                        </li>
                                        <?php if ($__eshEimzaLogin): ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" type="button" role="tab" id="mr-login-tab-eimza"
                                                    data-login-tab data-login-target="#mr-login-pane-eimza"
                                                    aria-controls="mr-login-pane-eimza" aria-selected="false" tabindex="-1">
                                                <i class="fa-solid fa-certificate me-2" aria-hidden="true"></i>E-imza ile giriş
                                            </button>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                    <div class="tab-content mr-login-tabs-content">
                                        <div class="tab-pane fade show active mr-login-mode-card mr-login-mode-card--normal"
                                             id="mr-login-pane-normal" role="tabpanel" aria-labelledby="mr-login-tab-normal" data-login-pane>
                                            <form action="<?= htmlspecialchars(esh_url('Auth', 'doLogin'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="mr-login-form" autocomplete="on" aria-labelledby="mr-login-tab-normal">
                            <?php include ROOT_PATH . '/views/partials/auth_login_csrf.php'; ?>
                                                <div class="mb-3">
                                                    <label for="mr-login-user" class="form-label small fw-semibold">Kullanıcı adı</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text border-end-0" id="mr-login-user-addon"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
                                                        <input id="mr-login-user" type="text" name="username" class="form-control border-start-0"
                                                               placeholder="Kullanıcı adınız" required autofocus
                                                               data-preserve-case autocomplete="username"
                                                               aria-describedby="mr-login-user-addon">
                                                    </div>
                                                </div>
                                                <div class="mb-1">
                                                    <label for="mr-login-pass" class="form-label small fw-semibold">Şifre</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text border-end-0" id="mr-login-pass-addon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                                                        <input id="mr-login-pass" type="password" name="password" class="form-control border-start-0"
                                                               placeholder="••••••••" required autocomplete="current-password"
                                                               aria-describedby="mr-login-pass-addon">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100 py-2 mt-3 fw-semibold rounded-3 mr-login-btn-submit">
                                                    Oturumu aç
                                                    <i class="fa-solid fa-arrow-right-to-bracket ms-2" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <?php if ($__eshEimzaLogin): ?>
                                        <div class="tab-pane fade mr-login-mode-card mr-login-mode-card--eimza"
                                             id="mr-login-pane-eimza" role="tabpanel" aria-labelledby="mr-login-tab-eimza" data-login-pane>
                                            <h3 class="h6 fw-bold mb-3 text-white">
                                                <i class="fa-solid fa-certificate me-2 mr-login-highlight" aria-hidden="true"></i>E-imza ile giriş
                                            </h3>
                                            <?php require ROOT_PATH . '/views/partials/login_eimza_panel.php'; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <footer class="mt-4 pt-2">
                                    <p class="small text-center mb-0 text-white opacity-75">Yardım için sistem yöneticinize başvurun.</p>
                                    <?php include ROOT_PATH . '/templates/meridian/partials/auth_login_extras.php'; ?>
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
