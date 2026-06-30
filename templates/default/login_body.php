<?php
$__lt = isset($GLOBALS['ESH_LOGIN_THEME']) ? (string) $GLOBALS['ESH_LOGIN_THEME'] : '';
$__lt = strtolower(trim($__lt));
$__lt = preg_replace('/[^a-z0-9_-]/', '', $__lt);
if ($__lt === '') {
    $__lt = \App\Helpers\ThemeViewHelper::siteThemeSlug();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8'); ?> | Giriş Yap</title>
    <?= esh_version_meta_tag(); ?>
    <?= esh_csrf_meta(); ?>

    <?= \App\Helpers\CdnAssetHelper::minimalAuthLayoutStylesHtml() ?>
<?php \App\Helpers\ThemeViewHelper::renderLoginStylesheetsHtml($__lt); ?>
    <script defer src="<?= htmlspecialchars(SITEURL . '/public/assets/form-input-uppercase.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script defer src="<?= htmlspecialchars(SITEURL . '/public/assets/form-submit-guard.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script defer src="<?= htmlspecialchars(SITEURL . '/public/assets/login-tabs.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</head>
<body class="page-login page-login-theme-<?= htmlspecialchars($__lt, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="card login-card login-card-split">
        <div class="login-header">
            <div class="login-brand-badge"><i class="fa-solid fa-house-medical-flag"></i></div>
            <h4 class="mb-1 fw-bold">SON <span class="text-white">EV</span></h4>
            <div class="login-brand-subtitle">Evde Sağlık Hizmetleri</div>
            <small class="opacity-75">Sürüm <?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8'); ?></small>
        </div>
        <div class="card-body p-4">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger d-flex align-items-center small">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="login-tabs" data-login-tabs>
                <ul class="nav nav-tabs login-tabs-nav mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" type="button" role="tab" id="login-tab-normal"
                                data-login-tab data-login-target="#login-pane-normal"
                                aria-controls="login-pane-normal" aria-selected="true">
                            <i class="fa-solid fa-key me-2"></i>Normal giriş
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" type="button" role="tab" id="login-tab-eimza"
                                data-login-tab data-login-target="#login-pane-eimza"
                                aria-controls="login-pane-eimza" aria-selected="false" tabindex="-1">
                            <i class="fa-solid fa-certificate me-2"></i>E-imza ile giriş
                        </button>
                    </li>
                </ul>
                <div class="tab-content login-tabs-content">
                    <div class="tab-pane fade show active login-mode-card login-mode-card--normal p-3 p-md-4"
                         id="login-pane-normal" role="tabpanel" aria-labelledby="login-tab-normal" data-login-pane>
                        <form action="<?= htmlspecialchars(esh_url('Auth', 'doLogin'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                            <?php include ROOT_PATH . '/views/partials/auth_login_csrf.php'; ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Kullanıcı Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                                    <input type="text" name="username" class="form-control border-start-0 shadow-none" placeholder="Kullanıcı adınız" required autofocus data-preserve-case>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0 shadow-none" placeholder="••••••••" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-login mt-3 shadow-sm">
                                Giriş Yap <i class="fa-solid fa-arrow-right-to-bracket ms-2"></i>
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade login-mode-card login-mode-card--eimza p-3 p-md-4"
                         id="login-pane-eimza" role="tabpanel" aria-labelledby="login-tab-eimza" data-login-pane>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 fw-bold text-secondary"><i class="fa-solid fa-certificate me-2 text-primary"></i>E-imza ile giriş</h6>
                            <span class="badge text-bg-secondary">Yakında</span>
                        </div>
                        <p class="small text-muted mb-3">Bu giriş tipi geçici olarak devre dışı. Aktif edildiğinde flash kontrolü ve PIN ile giriş yapılacak.</p>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-muted mb-1">TC Kimlik No</label>
                            <input type="text" class="form-control form-control-sm" placeholder="11 haneli TC" disabled>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-muted mb-1">E-imza PIN</label>
                            <input type="password" class="form-control form-control-sm" placeholder="Token PIN" disabled>
                        </div>
                        <button type="button" class="btn btn-outline-secondary w-100 btn-sm mt-2" disabled>
                            E-imza ile giriş yap
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 pb-4 text-center">
            <small class="text-muted">Yardım için sistem yöneticisine başvurun.</small>
            <?php include ROOT_PATH . '/templates/default/partials/auth_login_extras.php'; ?>
        </div>
    </div>
<?php include ROOT_PATH . '/views/partials/auth_login_password_toggle.php'; ?>
</body>
</html>
