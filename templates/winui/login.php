<?php
/**
 * WinUI Fluent giriş — tam sayfa (views/login_template.php yerine).
 * `templates/winui-{light,dark}/login.php` dosyaları `$GLOBALS['ESH_LOGIN_THEME']` ile bu şablonu içerir.
 */
$__lt = isset($GLOBALS['ESH_LOGIN_THEME']) && is_string($GLOBALS['ESH_LOGIN_THEME']) && trim($GLOBALS['ESH_LOGIN_THEME']) !== ''
    ? \App\Helpers\ThemeViewHelper::sanitizeThemeSlug($GLOBALS['ESH_LOGIN_THEME'])
    : 'winui';
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
    <?= esh_csp_script_src_tag(SITEURL . '/public/assets/form-input-uppercase.js', 'defer') ?>
    <?= esh_csp_script_src_tag(SITEURL . '/public/assets/form-submit-guard.js', 'defer') ?>
    <?= esh_csp_script_src_tag(SITEURL . '/public/assets/login-tabs.js', 'defer') ?>
</head>
<body class="page-login fluent-login-winui page-login-theme-<?= htmlspecialchars($__lt, ENT_QUOTES, 'UTF-8'); ?> min-vh-100">
    <div class="fluent-login-backdrop" aria-hidden="true"></div>

    <div class="container-xxl fluent-login-shell py-4 py-lg-5 position-relative">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10 col-xxl-8">
                <div class="card border-0 overflow-hidden fluent-login-split shadow-lg rounded-4">
                    <div class="row g-0">
                        <aside class="col-lg-4 fluent-login-brand-col d-none d-lg-flex flex-column justify-content-between p-4 p-xl-5 text-white">
                            <div>
                                <div class="fluent-login-brand-icon mb-4">
                                    <i class="fa-solid fa-house-medical-flag" aria-hidden="true"></i>
                                </div>
                                <p class="fluent-login-brand-kicker text-uppercase small fw-semibold opacity-90 mb-2 letter-spacing">Evde sağlık</p>
                                <h1 class="h3 fw-bold mb-2 lh-sm">SON <span class="fluent-login-highlight">EV</span></h1>
                                <p class="small opacity-85 mb-0 pe-lg-4">Fluent tabanlı arayüz ile güvenli oturum. Kimlik doğrulama sonrası panel ve sahada güncel araçlar açılır.</p>
                            </div>
                            <div class="mt-5 pt-lg-4">
                                <span class="badge fluent-login-version-pill">v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8'); ?></span>
                                <p class="small opacity-75 mt-3 mb-0"><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </aside>

                        <div class="col-lg-8 fluent-login-form-col bg-white">
                            <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                                <div class="d-lg-none text-center mb-4">
                                    <div class="fluent-login-brand-icon fluent-login-brand-icon-inline d-inline-flex text-white mb-2">
                                        <i class="fa-solid fa-house-medical-flag" aria-hidden="true"></i>
                                    </div>
                                    <h2 class="h5 fw-bold text-dark mb-0">SON <span class="text-primary">EV</span></h2>
                                    <p class="small text-muted mb-0">Evde Sağlık Hizmetleri</p>
                                </div>

                                <header class="mb-4">
                                    <h2 class="h4 fw-bold text-dark mb-1 fluent-login-heading">Oturum aç</h2>
                                    <p class="small text-muted mb-0">Normal giriş veya e-imza ile devam edin.</p>
                                </header>

                                <?php if (!empty($_SESSION['error'])): ?>
                                    <?php $__err = (string) $_SESSION['error']; unset($_SESSION['error']); ?>
                                    <div class="alert alert-danger d-flex align-items-start gap-2 small fluent-login-alert" role="alert">
                                        <i class="fa-solid fa-circle-exclamation mt-1 flex-shrink-0" aria-hidden="true"></i>
                                        <span><?= htmlspecialchars($__err, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="fluent-login-tabs" data-login-tabs>
                                    <ul class="nav nav-tabs fluent-login-tabs-nav mb-3" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" type="button" role="tab" id="fluent-login-tab-normal"
                                                    data-login-tab data-login-target="#fluent-login-pane-normal"
                                                    aria-controls="fluent-login-pane-normal" aria-selected="true">
                                                <i class="fa-solid fa-key me-2" aria-hidden="true"></i>Normal giriş
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" type="button" role="tab" id="fluent-login-tab-eimza"
                                                    data-login-tab data-login-target="#fluent-login-pane-eimza"
                                                    aria-controls="fluent-login-pane-eimza" aria-selected="false" tabindex="-1">
                                                <i class="fa-solid fa-certificate me-2" aria-hidden="true"></i>E-imza ile giriş
                                            </button>
                                        </li>
                                    </ul>
                                    <div class="tab-content fluent-login-tabs-content">
                                        <div class="tab-pane fade show active" id="fluent-login-pane-normal" role="tabpanel" aria-labelledby="fluent-login-tab-normal" data-login-pane>
                                            <form action="<?= htmlspecialchars(esh_url('Auth', 'doLogin'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="fluent-login-form" autocomplete="on">
                            <?php include ROOT_PATH . '/views/partials/auth_login_csrf.php'; ?>
                                                <div class="mb-3">
                                                    <label for="fluent-login-user" class="form-label small fw-semibold text-secondary">Kullanıcı adı</label>
                                                    <div class="input-group fluent-login-input-group">
                                                        <span class="input-group-text border-end-0 bg-white"><i class="fa-solid fa-user text-primary" aria-hidden="true"></i></span>
                                                        <input id="fluent-login-user" type="text" name="username" class="form-control border-start-0 ps-1" placeholder="Kullanıcı adınız" required autofocus data-preserve-case autocomplete="username">
                                                    </div>
                                                </div>
                                                <div class="mb-1">
                                                    <label for="fluent-login-pass" class="form-label small fw-semibold text-secondary">Şifre</label>
                                                    <div class="input-group fluent-login-input-group">
                                                        <span class="input-group-text border-end-0 bg-white"><i class="fa-solid fa-lock text-primary" aria-hidden="true"></i></span>
                                                        <input id="fluent-login-pass" type="password" name="password" class="form-control border-start-0 ps-1" placeholder="••••••••" required autocomplete="current-password">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100 py-3 mt-4 fluent-login-submit fw-semibold">
                                                    Oturumu aç
                                                    <i class="fa-solid fa-arrow-right-to-bracket ms-2" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <div class="tab-pane fade fluent-login-eimza" id="fluent-login-pane-eimza" role="tabpanel" aria-labelledby="fluent-login-tab-eimza" data-login-pane>
                                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                                <h3 class="h6 fw-bold text-dark mb-0">
                                                    <i class="fa-solid fa-certificate me-2 text-primary" aria-hidden="true"></i>E-imza ile giriş
                                                </h3>
                                                <span class="badge rounded-pill fluent-login-eimza-badge">Yakında</span>
                                            </div>
                                            <p class="small text-muted mb-3">Bu giriş tipi geçici olarak devre dışı. Aktif edildiğinde flash kontrolü ve PIN ile giriş yapılacak.</p>
                                            <div class="mb-3">
                                                <label for="fluent-login-eimza-tc" class="form-label small fw-semibold text-secondary">TC Kimlik No</label>
                                                <div class="input-group fluent-login-input-group">
                                                    <span class="input-group-text border-end-0 bg-white"><i class="fa-solid fa-id-card text-primary" aria-hidden="true"></i></span>
                                                    <input id="fluent-login-eimza-tc" type="text" class="form-control border-start-0 ps-1" placeholder="11 haneli TC" disabled autocomplete="off">
                                                </div>
                                            </div>
                                            <div class="mb-1">
                                                <label for="fluent-login-eimza-pin" class="form-label small fw-semibold text-secondary">E-imza PIN</label>
                                                <div class="input-group fluent-login-input-group">
                                                    <span class="input-group-text border-end-0 bg-white"><i class="fa-solid fa-lock text-primary" aria-hidden="true"></i></span>
                                                    <input id="fluent-login-eimza-pin" type="password" class="form-control border-start-0 ps-1" placeholder="Token PIN" disabled autocomplete="off">
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary w-100 py-3 mt-4 fw-semibold" disabled>
                                                E-imza ile giriş yap
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <p class="small text-muted text-center mt-4 mb-0">Yardım için sistem yöneticinize başvurun.</p>
                                <?php include ROOT_PATH . '/views/partials/auth_login_extras.php'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><?php include ROOT_PATH . '/views/partials/auth_login_password_toggle.php'; ?>

</body>
</html>
