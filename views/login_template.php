<?php
use App\Helpers\AppSettings;
use App\Helpers\OperationalSettings;

$__eshEimzaLogin = AppSettings::isModuleEnabled('eimza_login');
$__eshMaintenanceActive = OperationalSettings::isMaintenanceModeEnabled();
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
    <?= esh_csp_script_src_tag(SITEURL . '/public/assets/form-input-uppercase.js', 'defer') ?>
    <?= esh_csp_script_src_tag(SITEURL . '/public/assets/form-submit-guard.js', 'defer') ?>
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
            <?php if ($__eshMaintenanceActive): ?>
                <div class="alert alert-warning d-flex align-items-start small mb-3" role="alert">
                    <i class="fa-solid fa-screwdriver-wrench me-2 mt-1"></i>
                    <div>
                        <strong>Site bakımda.</strong> Yalnızca <?= htmlspecialchars(mb_strtolower(\App\Helpers\AuthHelper::adminLevelLabel(\App\Helpers\AuthHelper::ROLE_PLATFORM_OWNER), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?> girişi kabul edilir.
                        <?php if (OperationalSettings::maintenanceMessage() !== ''): ?>
                            <span class="d-block mt-1 mb-0"><?= htmlspecialchars(OperationalSettings::maintenanceMessage(), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger d-flex align-items-center small">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= \App\Helpers\FlashHelper::escapeForHtml((string) $_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4 login-split-panels">
                <div class="col-12 <?= $__eshEimzaLogin ? 'col-lg-6' : '' ?>">
                    <div class="border rounded-3 p-3 p-md-4 h-100 login-mode-card login-mode-card--normal">
                        <h6 class="fw-bold text-primary mb-3 login-panel-title"><i class="fa-solid fa-key me-2"></i>Normal giriş</h6>
                        <form action="<?= htmlspecialchars(esh_url('Auth', 'doLogin'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                            <?php include ROOT_PATH . '/views/partials/auth_login_csrf.php'; ?>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted login-field-label">Kullanıcı Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                                    <input type="text" name="username" class="form-control border-start-0 shadow-none" placeholder="Kullanıcı adınız" required autofocus data-preserve-case>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted login-field-label">Şifre</label>
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
                </div>

                <?php if ($__eshEimzaLogin): ?>
                <div class="col-12 col-lg-6">
                    <div class="border rounded-3 p-3 p-md-4 h-100 bg-light-subtle login-mode-card login-mode-card--eimza">
                        <h6 class="mb-3 fw-semibold text-secondary login-panel-title"><i class="fa-solid fa-certificate me-2 text-primary"></i>E-imza ile giriş</h6>
                        <?php require ROOT_PATH . '/views/partials/login_eimza_panel.php'; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer bg-white border-0 pb-4 text-center">
            <small class="text-muted">Yardım için sistem yöneticisine başvurun.</small>
            <?php include ROOT_PATH . '/views/partials/auth_login_extras.php'; ?>
        </div>
    </div>
<?php include ROOT_PATH . '/views/partials/auth_login_password_toggle.php'; ?>
</body>
</html>
