<?php
declare(strict_types=1);

/**
 * Web kurulum sihirbazı — config.php yüklemez.
 */
require_once dirname(__DIR__) . '/app/Install/Installer.php';
require_once dirname(__DIR__) . '/app/Core/DbSqlHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CdnAssetHelper.php';

use App\Core\DbSqlHelper;

use App\Helpers\CdnAssetHelper;
use App\Install\Installer;

if (Installer::isLocked()) {
    header('Content-Type: text/html; charset=UTF-8');
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Kurulum</title></head>';
    echo '<body style="font-family:system-ui,sans-serif;max-width:42rem;margin:2rem auto;padding:0 1rem;">';
    echo '<h1>Kurulum tamamlanmış</h1>';
    echo '<p><code>config/install.lock</code> mevcut. Sihirbaz tekrar çalıştırılamaz.</p>';
    echo '<p>Güvenlik için üretimde <code>public/install.php</code> dosyasını silin veya yeniden adlandırın. Yerelde <code>ESH_ALLOW_INSTALL_PHP=1</code>.</p>';
    echo '<p><a href="index.php">Panele git</a></p></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['install_api'] ?? '') === '1') {
    ob_start();
    header('Content-Type: application/json; charset=UTF-8');
    $postedDriver = trim((string) ($_POST['db_driver'] ?? ''));
    $prereq = Installer::prerequisiteErrors($postedDriver !== '' ? $postedDriver : null);
    if ($prereq !== []) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => implode(' ', $prereq)], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $step = trim((string) ($_POST['step'] ?? ''));
    $result = Installer::runInstallStep($step, $_POST);
    ob_end_clean();
    if (empty($result['ok'])) {
        http_response_code(400);
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: text/html; charset=UTF-8');

$h = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

$defaultSite = Installer::detectSiteUrlFromRequest();
$hardPrereq = Installer::hardPrerequisiteErrors();
$driverCapabilities = Installer::installDriverCapabilities();
$availableDrivers = Installer::installAvailableDrivers();
$postedDriver = ($_SERVER['REQUEST_METHOD'] === 'POST') ? trim((string) ($_POST['db_driver'] ?? '')) : '';
$prereq = Installer::prerequisiteErrors($postedDriver !== '' ? $postedDriver : null);

$defaultDbDriver = (string) ($_POST['db_driver'] ?? '');
if ($defaultDbDriver === '' || !Installer::isInstallDriverAvailable($defaultDbDriver)) {
    $defaultDbDriver = $availableDrivers[0] ?? 'mysql';
}

$showWizardForm = $hardPrereq === [] && $availableDrivers !== [];
$driverPrereqOnly = $hardPrereq === [] && $availableDrivers === [] ? Installer::driverPrerequisiteErrors(null) : [];
$seedCatalogJson = json_encode(Installer::seedCatalog(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if ($seedCatalogJson === false) {
    $seedCatalogJson = '[]';
}
$availableDriversJson = json_encode($availableDrivers, JSON_UNESCAPED_UNICODE);
if ($availableDriversJson === false) {
    $availableDriversJson = '[]';
}
$availableDriverLabels = [];
foreach ($driverCapabilities as $cap) {
    if ($cap['available']) {
        $availableDriverLabels[] = $cap['select_label'];
    }
}

$defaultHost = match (DbSqlHelper::normalizeDbDriver($defaultDbDriver)) {
    'sqlsrv' => 'localhost\\SQLEXPRESS',
    'pgsql' => '127.0.0.1',
    'sqlite' => 'storage/data',
    'oci' => '127.0.0.1',
    default => '127.0.0.1',
};
$defaultUser = match (DbSqlHelper::normalizeDbDriver($defaultDbDriver)) {
    'sqlsrv' => 'esh_app',
    'pgsql' => 'postgres',
    'sqlite' => '',
    'oci' => 'system',
    default => 'root',
};
$defaultDbName = DbSqlHelper::normalizeDbDriver($defaultDbDriver) === 'sqlite' ? 'esh4.sqlite' : 'esh4';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ESH — Kurulum</title>
    <?= CdnAssetHelper::installWizardHeadAssetsHtml() ?>
    <link rel="stylesheet" href="assets/install-wizard.css">
</head>
<body class="esh-install-root text-white">
<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <div class="card esh-install-card rounded-4 overflow-hidden">
                <div class="esh-install-hero p-4 p-md-5 text-center">
                    <h1 class="h3 fw-bold mb-2"><i class="fa-solid fa-screwdriver-wrench me-2 text-primary"></i>ESH Kurulum</h1>
                    <p class="text-muted mb-0 small">Veritabanı şeması, seed verileri, süper yönetici ve <code>config.local.php</code></p>
                </div>

                <?php if ($hardPrereq !== []) { ?>
                    <div class="p-4 p-md-5">
                        <div class="alert alert-danger border-0 shadow-sm mb-0" role="alert">
                            <strong>Önkoşullar sağlanmıyor</strong>
                            <ul class="mb-0 mt-2 small"><?php foreach ($hardPrereq as $e) { ?><li><?= $h($e) ?></li><?php } ?></ul>
                        </div>
                    </div>
                <?php } elseif (!$showWizardForm) { ?>
                    <div class="p-4 p-md-5">
                        <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
                            <strong>Veritabanı sürücüsü bulunamadı</strong>
                            <p class="mb-2 mt-2 small">Sunucuda kurulum yapılabilecek PDO sürücüsü ve şema dosyası eşleşmesi yok.</p>
                            <?php if ($driverPrereqOnly !== []) { ?>
                                <ul class="mb-0 small"><?php foreach ($driverPrereqOnly as $e) { ?><li><?= $h($e) ?></li><?php } ?></ul>
                            <?php } ?>
                        </div>
                        <div class="card border-0 bg-light">
                            <div class="card-body small">
                                <h2 class="h6 fw-bold mb-3">Sunucuda tespit edilen sürücüler</h2>
                                <ul class="list-unstyled mb-0" id="esh-db-driver-status-static">
                                    <?php foreach ($driverCapabilities as $cap) { ?>
                                        <li class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                            <span class="fw-semibold"><?= $h($cap['select_label']) ?></span>
                                            <?php if ($cap['available']) { ?>
                                                <span class="badge text-bg-success">Kuruluma hazır</span>
                                            <?php } else { ?>
                                                <span class="badge text-bg-secondary">Eksik</span>
                                                <span class="text-muted"><?= $h($cap['status_message']) ?></span>
                                            <?php } ?>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php } elseif ($showWizardForm) { ?>
                    <div class="bg-white text-dark">
                        <div class="px-4 pt-4 pb-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center gap-3 esh-install-steps-row flex-wrap">
                                <div class="esh-install-step esh-install-step--active small" data-step-indicator="0">
                                    <span class="esh-install-step-badge me-2">1</span>Veritabanı
                                </div>
                                <div class="esh-install-step esh-install-step--pending small" data-step-indicator="1">
                                    <span class="esh-install-step-badge me-2">2</span>Site URL
                                </div>
                                <div class="esh-install-step esh-install-step--pending small" data-step-indicator="2">
                                    <span class="esh-install-step-badge me-2">3</span>Yönetici
                                </div>
                            </div>
                        </div>

                        <div id="esh-install-wizard-wrap">
                            <form id="esh-install-form" class="p-4 p-md-5 needs-validation" novalidate>
                                <section id="esh-step-0" class="esh-install-pane" data-step="0">
                                    <h3 class="h6 fw-bold text-primary mb-4">
                                        <i class="fa-solid fa-database me-2"></i><span id="esh-db-step-title">Veritabanı bağlantısı</span>
                                    </h3>
                                    <div class="row g-3">
                                    <div class="col-12">
                                        <div class="alert alert-light border small mb-0" id="esh-db-driver-detect-panel" role="status">
                                            <div class="fw-semibold mb-2"><i class="fa-solid fa-plug-circle-check me-1 text-primary"></i>Sunucuda tespit edilen sürücüler</div>
                                            <?php if ($availableDriverLabels !== []) { ?>
                                                <p class="mb-2 text-success-emphasis">
                                                    Kurulum yapılabilir:
                                                    <strong><?= $h(implode(', ', $availableDriverLabels)) ?></strong>
                                                </p>
                                            <?php } ?>
                                            <ul class="list-unstyled mb-0 esh-driver-status-list" id="esh-db-driver-status">
                                                <?php foreach ($driverCapabilities as $cap) { ?>
                                                    <li class="esh-driver-status-item d-flex flex-wrap align-items-center gap-2 mb-2<?= $cap['driver'] === $defaultDbDriver ? ' esh-driver-status--active' : '' ?>"
                                                        data-driver="<?= $h($cap['driver']) ?>">
                                                        <span class="fw-semibold"><?= $h($cap['select_label']) ?></span>
                                                        <?php if ($cap['available']) { ?>
                                                            <span class="badge text-bg-success">Hazır</span>
                                                        <?php } else { ?>
                                                            <span class="badge text-bg-secondary">Eksik</span>
                                                            <span class="text-muted"><?= $h($cap['status_message']) ?></span>
                                                        <?php } ?>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                            <p class="form-text mb-0 mt-2">Liste, sunucuda yüklü PDO eklentilerine ve şema dosyalarına göre otomatik filtrelenir.</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="db_driver">Veritabanı türü</label>
                                        <select class="form-select" id="db_driver" name="db_driver" required>
                                            <?php foreach ($driverCapabilities as $cap) { ?>
                                                <option value="<?= $h($cap['driver']) ?>"
                                                    <?= $defaultDbDriver === $cap['driver'] ? 'selected' : '' ?>
                                                    <?= $cap['available'] ? '' : 'disabled' ?>>
                                                    <?= $h($cap['select_label']) ?><?= $cap['available'] ? '' : ' (' . $h($cap['status_message']) . ')' ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                        <div class="form-text">Yalnızca sunucuda hazır olan sürücüler seçilebilir.</div>
                                    </div>
                                        <div class="col-12">
                                            <div class="accordion" id="esh-db-driver-help">
                                                <div class="accordion-item border rounded-3 overflow-hidden">
                                                    <h4 class="accordion-header">
                                                        <button class="accordion-button collapsed py-2 small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#esh-db-driver-help-body" aria-expanded="false">
                                                            Veritabanı seçenekleri ve bulut servisleri
                                                        </button>
                                                    </h4>
                                                    <div id="esh-db-driver-help-body" class="accordion-collapse collapse" data-bs-parent="#esh-db-driver-help">
                                                        <div class="accordion-body small text-muted">
                                                            <p class="mb-2"><strong>MySQL / MariaDB</strong> — <code>mysql</code>, <code>pdo_mysql</code>, <code>database/schemas/schema.sql</code></p>
                                                            <p class="mb-2"><strong>SQL Server</strong> — <code>sqlsrv</code>, <code>pdo_sqlsrv</code>, <code>database/schemas/schema.mssql.sql</code></p>
                                                            <p class="mb-2"><strong>PostgreSQL</strong> — <code>pgsql</code>, <code>pdo_pgsql</code>, <code>database/schemas/schema.pgsql.sql</code></p>
                                                            <p class="mb-2"><strong>SQLite</strong> — <code>sqlite</code>, <code>pdo_sqlite</code>; dosya yolu veya <code>storage/data</code> + dosya adı</p>
                                                            <p class="mb-2"><strong>Oracle</strong> — <code>oci</code>, <code>pdo_oci</code>; servis adı (ör. <code>XEPDB1</code>) veritabanı alanına yazılır</p>
                                                            <p class="mb-0"><strong>Bulut (RDS, Azure, vb.)</strong> — Uygun sürücüyü seçin; veritabanını panelden önceden oluşturun. Açılır liste sunucuda hazır sürücülere göre filtrelenir.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="db_port">Port <span class="text-muted fw-normal">(isteğe bağlı)</span></label>
                                            <input class="form-control" id="db_port" name="db_port" type="text" inputmode="numeric" pattern="[0-9]*"
                                                   value="<?= $h((string) ($_POST['db_port'] ?? '')) ?>" autocomplete="off" placeholder="">
                                            <div class="form-text" id="esh-db-port-hint">MySQL için genelde boş (3306).</div>
                                        </div>
                                        <div class="col-md-6" id="esh-db-host-wrap">
                                            <label class="form-label" for="db_host" id="esh-db-host-label">Sunucu</label>
                                            <input class="form-control" id="db_host" name="db_host" type="text" required autocomplete="off"
                                                   value="<?= $h((string) ($_POST['db_host'] ?? $defaultHost)) ?>">
                                            <div class="form-text" id="esh-db-host-hint">MySQL: <code>127.0.0.1</code></div>
                                            <div class="invalid-feedback">Sunucu gerekli.</div>
                                        </div>
                                        <div class="col-md-6" id="esh-db-user-wrap">
                                            <label class="form-label" for="db_user">Kullanıcı</label>
                                            <input class="form-control" id="db_user" name="db_user" type="text" required autocomplete="username"
                                                   value="<?= $h((string) ($_POST['db_user'] ?? $defaultUser)) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="db_pass">Şifre</label>
                                            <input class="form-control" id="db_pass" name="db_pass" type="password" autocomplete="current-password"
                                                   value="<?= $h((string) ($_POST['db_pass'] ?? '')) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="db_name" id="esh-db-name-label">Veritabanı adı</label>
                                            <input class="form-control" id="db_name" name="db_name" type="text" required
                                                   value="<?= $h((string) ($_POST['db_name'] ?? $defaultDbName)) ?>">
                                            <div class="form-text" id="esh-db-name-hint"></div>
                                            <div class="invalid-feedback">Geçerli bir veritabanı adı girin.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="db_prefix">Tablo öneki</label>
                                            <input class="form-control" id="db_prefix" name="db_prefix" type="text" required pattern="[a-zA-Z0-9_]{1,32}"
                                                   value="<?= $h((string) ($_POST['db_prefix'] ?? Installer::DEFAULT_DB_PREFIX)) ?>">
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="create_database" name="create_database" value="1"
                                                    <?= !empty($_POST['create_database']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="create_database" id="esh-create-db-label">Veritabanı yoksa oluştur</label>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section id="esh-step-1" class="esh-install-pane d-none" data-step="1">
                                    <h3 class="h6 fw-bold text-primary mb-4"><i class="fa-solid fa-link me-2"></i>Site adresi</h3>
                                    <label class="form-label" for="site_url">Site taban URL</label>
                                    <input class="form-control" id="site_url" name="site_url" type="url" required
                                           value="<?= $h((string) ($_POST['site_url'] ?? $defaultSite)) ?>" placeholder="http://localhost">
                                    <div class="form-text">Genelde <code>public</code> klasörünün bir üst dizini.</div>
                                    <div class="invalid-feedback">Geçerli bir URL girin.</div>
                                </section>

                                <section id="esh-step-2" class="esh-install-pane d-none" data-step="2">
                                    <h3 class="h6 fw-bold text-primary mb-4"><i class="fa-solid fa-user-shield me-2"></i>Süper yönetici</h3>
                                    <div class="alert alert-info border-0 small mb-3">
                                        Giriş bilgileri sabittir:
                                        <strong><?= $h(Installer::SUPERADMIN_USER) ?></strong> /
                                        <strong><?= $h(Installer::SUPERADMIN_PASS) ?></strong>
                                    </div>
                                    <label class="form-label" for="admin_name">Görünen ad (isteğe bağlı)</label>
                                    <input class="form-control" id="admin_name" name="admin_name" type="text"
                                           value="<?= $h((string) ($_POST['admin_name'] ?? Installer::SUPERADMIN_NAME)) ?>">
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label" for="admin_unvan">Ünvan</label>
                                            <select class="form-select" id="admin_unvan" name="admin_unvan">
                                                <?php
                                                $defaultAdminUnvan = (string) ($_POST['admin_unvan'] ?? Installer::SUPERADMIN_UNVAN);
                                                foreach (Installer::adminUnvanChoices() as $val => $label) {
                                                    $sel = $defaultAdminUnvan === $val ? ' selected' : '';
                                                    echo '<option value="' . $h($val) . '"' . $sel . '>' . $h($label) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="admin_email">E-posta</label>
                                            <input class="form-control" id="admin_email" name="admin_email" type="email" required
                                                   value="<?= $h((string) ($_POST['admin_email'] ?? Installer::SUPERADMIN_EMAIL)) ?>">
                                        </div>
                                    </div>
                                </section>

                                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 d-none" id="esh-install-prev">
                                        <i class="fa-solid fa-arrow-left me-1"></i>Geri
                                    </button>
                                    <div class="ms-auto d-flex gap-2">
                                        <button type="button" class="btn btn-primary rounded-pill px-4" id="esh-install-next">
                                            İleri<i class="fa-solid fa-arrow-right ms-1"></i>
                                        </button>
                                        <button type="submit" class="btn btn-success rounded-pill px-4 d-none" id="esh-install-submit">
                                            <i class="fa-solid fa-play me-1"></i>Kurulumu çalıştır
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div id="esh-install-running" class="d-none p-4 p-md-5" aria-live="polite">
                            <h3 class="h6 fw-bold text-primary mb-3"><i class="fa-solid fa-spinner fa-spin me-2"></i>Kurulum çalışıyor</h3>
                            <p id="esh-install-run-status" class="small text-muted mb-3">Başlatılıyor…</p>
                            <div class="progress mb-4 rounded-pill" style="height:12px;" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                                <div id="esh-install-run-progress" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:0%" aria-valuenow="0">0%</div>
                            </div>
                            <div class="small fw-semibold text-secondary mb-2">Seed tabloları</div>
                            <ul id="esh-install-seed-list" class="list-group list-group-flush esh-install-seed-list border rounded-3 overflow-hidden"></ul>
                        </div>

                        <div id="esh-install-result" class="d-none p-4 p-md-5"></div>
                    </div>
                <?php } ?>
            </div>
            <p class="text-center small text-white-50 mt-4 mb-0">
                <i class="fa-regular fa-file-code me-1"></i>
                <code>database/schemas/schema.sql</code> veya <code>database/schemas/schema.mssql.sql</code> + seed dosyaları
            </p>
        </div>
    </div>
</div>
<script>
(function () {
    var availableDrivers = <?= $availableDriversJson ?>;

    var $form = $('#esh-install-form');
    if (!$form.length) {
        return;
    }

    var $dbDriver = $('#db_driver');
    var $dbPort = $('#db_port');
    var $createDbLabel = $('#esh-create-db-label');
    var $dbHostHint = $('#esh-db-host-hint');
    var $dbPortHint = $('#esh-db-port-hint');
    var $dbStepTitle = $('#esh-db-step-title');

    var $dbHost = $('#db_host');
    var $dbUser = $('#db_user');
    var $dbName = $('#db_name');
    var $dbHostWrap = $('#esh-db-host-wrap');
    var $dbUserWrap = $('#esh-db-user-wrap');
    var $dbPortWrap = $('#db_port').closest('.col-md-6');
    var $dbHostLabel = $('#esh-db-host-label');
    var $dbNameLabel = $('#esh-db-name-label');
    var $dbNameHint = $('#esh-db-name-hint');
    var $createDbWrap = $('#create_database').closest('.col-12');

    function syncDbDriverUi() {
        var driver = $dbDriver.val();
        var $sel = $dbDriver.find('option:selected');
        if (($sel.length === 0 || $sel.prop('disabled')) && availableDrivers.length > 0) {
            $dbDriver.val(availableDrivers[0]);
            driver = availableDrivers[0];
        }

        $('#esh-db-driver-status .esh-driver-status-item').removeClass('esh-driver-status--active');
        $('#esh-db-driver-status .esh-driver-status-item[data-driver="' + driver + '"]').addClass('esh-driver-status--active');

        var titles = {
            mysql: 'MySQL bağlantısı',
            sqlsrv: 'SQL Server bağlantısı',
            pgsql: 'PostgreSQL bağlantısı',
            sqlite: 'SQLite dosyası',
            oci: 'Oracle bağlantısı'
        };
        $dbStepTitle.text(titles[driver] || 'Veritabanı bağlantısı');

        var isSqlite = driver === 'sqlite';
        var isOci = driver === 'oci';
        $dbHostWrap.toggleClass('d-none', false);
        $dbUserWrap.toggleClass('d-none', isSqlite);
        $dbPortWrap.toggleClass('d-none', isSqlite);
        $dbHost.prop('required', !isSqlite);
        $dbUser.prop('required', !isSqlite);

        if (driver === 'sqlsrv') {
            $dbHostHint.html('SQL Server: <code>localhost\\SQLEXPRESS</code>, <code>.</code>');
            $dbPortHint.text('SQL Server için genelde 1433.');
            $dbPort.attr('placeholder', '1433');
            $dbHostLabel.text('Sunucu');
            $dbNameLabel.text('Veritabanı adı');
            $dbNameHint.text('');
            $createDbLabel.text('Veritabanı yoksa oluştur (CREATE DATABASE)');
            $createDbWrap.removeClass('d-none');
        } else if (driver === 'pgsql') {
            $dbHostHint.html('PostgreSQL: <code>127.0.0.1</code>');
            $dbPortHint.text('PostgreSQL için genelde 5432.');
            $dbPort.attr('placeholder', '5432');
            $dbHostLabel.text('Sunucu');
            $dbNameLabel.text('Veritabanı adı');
            $dbNameHint.text('Kurulum önce postgres veritabanına bağlanır.');
            $createDbLabel.text('Veritabanı yoksa oluştur (CREATE DATABASE)');
            $createDbWrap.removeClass('d-none');
        } else if (driver === 'sqlite') {
            $dbHostHint.html('Dizin yolu (boş = <code>storage/data</code>) veya tam dosya yolu');
            $dbPortHint.text('SQLite için port kullanılmaz.');
            $dbPort.attr('placeholder', '');
            $dbHostLabel.text('Dizin / dosya yolu');
            $dbNameLabel.text('Veritabanı dosyası');
            $dbNameHint.text('Örn. esh4.sqlite veya storage/data/esh4.sqlite');
            $createDbLabel.text('Dosya yoksa oluştur');
            $createDbWrap.removeClass('d-none');
        } else if (driver === 'oci') {
            $dbHostHint.html('Oracle: <code>127.0.0.1</code> veya RAC host');
            $dbPortHint.text('Oracle için genelde 1521.');
            $dbPort.attr('placeholder', '1521');
            $dbHostLabel.text('Sunucu');
            $dbNameLabel.text('Servis adı (SID / PDB)');
            $dbNameHint.text('Örn. ORCL, XEPDB1 — CREATE DATABASE desteklenmez.');
            $createDbWrap.addClass('d-none');
            $('#create_database').prop('checked', false);
        } else {
            $dbHostHint.html('MySQL: <code>127.0.0.1</code> veya <code>localhost</code>');
            $dbPortHint.text('MySQL için genelde boş (3306).');
            $dbPort.attr('placeholder', '');
            $dbHostLabel.text('Sunucu');
            $dbNameLabel.text('Veritabanı adı');
            $dbNameHint.text('');
            $createDbLabel.text('Veritabanı yoksa oluştur (CREATE DATABASE IF NOT EXISTS)');
            $createDbWrap.removeClass('d-none');
        }
    }

    $dbDriver.on('change', syncDbDriverUi);
    syncDbDriverUi();

    var step = 0;
    var maxStep = 2;
    var $panes = $('.esh-install-pane');
    var $indicators = $('[data-step-indicator]');
    var $prev = $('#esh-install-prev');
    var $next = $('#esh-install-next');
    var $submit = $('#esh-install-submit');

    function showStep(n) {
        step = n;
        $panes.addClass('d-none').filter('[data-step="' + n + '"]').removeClass('d-none');
        $indicators.each(function () {
            var i = parseInt($(this).attr('data-step-indicator'), 10);
            $(this).removeClass('esh-install-step--active esh-install-step--done esh-install-step--pending');
            if (i < n) {
                $(this).addClass('esh-install-step--done');
            } else if (i === n) {
                $(this).addClass('esh-install-step--active');
            } else {
                $(this).addClass('esh-install-step--pending');
            }
        });
        $prev.toggleClass('d-none', n === 0);
        $next.toggleClass('d-none', n === maxStep);
        $submit.toggleClass('d-none', n !== maxStep);
    }

    function validatePane(n) {
        var $pane = $panes.filter('[data-step="' + n + '"]');
        var ok = true;
        $pane.find('input, select').each(function () {
            if (!this.checkValidity()) {
                ok = false;
            }
        });
        return ok;
    }

    $next.on('click', function () {
        if (!validatePane(step)) {
            $form.addClass('was-validated');
            if (window.toastr) {
                toastr.error('Eksik veya hatalı alanlar var.');
            }
            return;
        }
        showStep(Math.min(maxStep, step + 1));
    });

    $prev.on('click', function () {
        showStep(Math.max(0, step - 1));
    });

    var seedCatalog = <?= $seedCatalogJson ?>;
    var $wizardWrap = $('#esh-install-wizard-wrap');
    var $running = $('#esh-install-running');
    var $runProg = $('#esh-install-run-progress');
    var $runStatus = $('#esh-install-run-status');
    var $seedList = $('#esh-install-seed-list');
    var $result = $('#esh-install-result');

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setRunProgress(percent) {
        var pct = Math.max(0, Math.min(100, parseInt(percent, 10) || 0));
        $runProg.css('width', pct + '%').attr('aria-valuenow', pct).text(pct + '%');
    }

    function renderSeedList(seeds) {
        $seedList.empty();
        (seeds || []).forEach(function (item) {
            $seedList.append(
                '<li class="list-group-item esh-install-seed-item esh-install-seed-item--pending d-flex align-items-center gap-2 py-2" data-seed-index="' + item.index + '">' +
                '<span class="esh-install-seed-icon text-muted"><i class="fa-regular fa-circle"></i></span>' +
                '<span class="flex-grow-1"><span class="fw-semibold">' + escapeHtml(item.label) + '</span>' +
                '<span class="d-block small text-muted">' + escapeHtml(item.file) + '</span></span></li>'
            );
        });
    }

    function setSeedState(index, state) {
        var $item = $seedList.find('[data-seed-index="' + index + '"]');
        if (!$item.length) {
            return;
        }
        $item.removeClass('esh-install-seed-item--pending esh-install-seed-item--active esh-install-seed-item--done esh-install-seed-item--error');
        $item.addClass('esh-install-seed-item--' + state);
        var icon = '<i class="fa-regular fa-circle"></i>';
        if (state === 'active') {
            icon = '<i class="fa-solid fa-spinner fa-spin text-primary"></i>';
        } else if (state === 'done') {
            icon = '<i class="fa-solid fa-circle-check text-success"></i>';
        } else if (state === 'error') {
            icon = '<i class="fa-solid fa-circle-xmark text-danger"></i>';
        }
        $item.find('.esh-install-seed-icon').html(icon);
    }

    function postInstallStep(stepName, extra) {
        var fd = new FormData($form[0]);
        fd.append('install_api', '1');
        fd.append('step', stepName);
        if (extra) {
            Object.keys(extra).forEach(function (k) {
                fd.append(k, extra[k]);
            });
        }
        return fetch(window.location.href, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.text().then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    var snippet = (text || '').replace(/\s+/g, ' ').trim().slice(0, 240);
                    return {
                        ok: false,
                        message: 'Sunucu geçersiz yanıt döndürdü (JSON bekleniyordu). ' + snippet
                    };
                }
                if (!res.ok && data && data.message) {
                    data.ok = false;
                }
                return data;
            });
        });
    }

    function showInstallError(message) {
        $running.addClass('d-none');
        $result.removeClass('d-none').html(
            '<div class="alert alert-danger border-0 shadow-sm" role="alert">' +
            '<strong>Kurulum başarısız</strong><p class="mb-0 mt-2 small">' + escapeHtml(message) + '</p>' +
            '<button type="button" class="btn btn-sm btn-outline-danger rounded-pill mt-3" id="esh-install-retry">Yeniden dene</button></div>'
        );
        $('#esh-install-retry').on('click', function () {
            $result.addClass('d-none').empty();
            $wizardWrap.removeClass('d-none');
            showStep(0);
        });
        if (window.toastr) {
            toastr.error(message);
        }
    }

    function showInstallSuccess(message) {
        $running.addClass('d-none');
        $result.removeClass('d-none').html(
            '<div class="alert alert-success border-0 shadow-sm" role="status">' +
            '<h3 class="h6 fw-bold">Kurulum tamamlandı</h3>' +
            '<p class="mb-3 small">' + escapeHtml(message) + '</p>' +
            '<p class="small text-muted mb-3">Üretimde <code>public/install.php</code> dosyasını kaldırın.</p>' +
            '<a class="btn btn-success rounded-pill px-4" href="index.php">Panele git</a></div>'
        );
        if (window.toastr) {
            toastr.success(message);
        }
    }

    function startInstallRun() {
        $wizardWrap.addClass('d-none');
        $running.removeClass('d-none');
        $result.addClass('d-none').empty();
        setRunProgress(0);
        $runStatus.text('Veritabanı bağlantısı kuruluyor…');
        renderSeedList(seedCatalog);

        postInstallStep('bootstrap').then(function (boot) {
            if (!boot.ok) {
                showInstallError(boot.message || 'Bağlantı hatası.');
                return null;
            }
            if (boot.seeds && boot.seeds.length) {
                renderSeedList(boot.seeds);
            }
            setRunProgress(boot.percent || 5);
            $runStatus.text('Veritabanı şeması oluşturuluyor…');
            return postInstallStep('schema');
        }).then(function (schema) {
            if (!schema) {
                return null;
            }
            if (!schema.ok) {
                showInstallError(schema.message || 'Şema hatası.');
                return null;
            }
            setRunProgress(schema.percent || 15);
            var total = parseInt(schema.seed_total, 10) || seedCatalog.length;
            var chain = Promise.resolve();
            for (var i = 0; i < total; i++) {
                (function (seedIndex) {
                    chain = chain.then(function (prev) {
                        if (prev === false) {
                            return false;
                        }
                        setSeedState(seedIndex, 'active');
                        var label = seedCatalog[seedIndex] ? seedCatalog[seedIndex].label : ('Seed ' + (seedIndex + 1));
                        $runStatus.text('Yükleniyor: ' + label);
                        return postInstallStep('seed', { seed_index: String(seedIndex) }).then(function (seedRes) {
                            if (!seedRes.ok) {
                                setSeedState(seedIndex, 'error');
                                showInstallError(seedRes.message || 'Seed hatası.');
                                return false;
                            }
                            setSeedState(seedIndex, 'done');
                            setRunProgress(seedRes.percent || 0);
                            return true;
                        });
                    });
                })(i);
            }
            return chain.then(function (ok) {
                if (ok === false) {
                    return null;
                }
                $runStatus.text('Yönetici ve yapılandırma kaydediliyor…');
                return postInstallStep('finalize');
            });
        }).then(function (finalRes) {
            if (!finalRes) {
                return;
            }
            if (!finalRes.ok) {
                showInstallError(finalRes.message || 'Sonlandırma hatası.');
                return;
            }
            setRunProgress(100);
            $runStatus.text('Tamamlandı.');
            showInstallSuccess(finalRes.message || 'Kurulum tamamlandı.');
        }).catch(function (err) {
            showInstallError(err && err.message ? err.message : 'Beklenmeyen ağ hatası.');
        });
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        for (var i = 0; i <= maxStep; i++) {
            if (!validatePane(i)) {
                $form.addClass('was-validated');
                showStep(i);
                if (window.toastr) {
                    toastr.error('Eksik veya hatalı alanlar var.');
                }
                return;
            }
        }
        startInstallRun();
    });

    showStep(0);
})();
</script>
</body>
</html>
