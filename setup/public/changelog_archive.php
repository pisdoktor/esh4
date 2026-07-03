<?php
declare(strict_types=1);
/**
 * Eski site + 2.x dönemine ait ayrıntılı günlük (gruplu kartlar, collapse).
 * Kaynak dosya: XAMPP htdocs kökündeki `changelog.php` (3.0.0 klasörünün bir üstü).
 * Kurulumda yalnızca `3.0.0/` kopyalanmışsa bu dosya bulunamaz — uyarı verilir.
 */
require_once dirname(__DIR__) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $loginUrl = rtrim((string) SITEURL, '/') . '/public/' . ltrim(esh_url('Auth', 'login'), '/');
    header('Location: ' . $loginUrl);
    exit;
}

$archive = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'changelog.php';
if (!is_file($archive)) {
    header('Content-Type: text/html; charset=UTF-8', true, 404);
    ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Arşiv günlük bulunamadı</title>
    <?= \App\Helpers\CdnAssetHelper::minimalAuthLayoutStylesHtml() ?>
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 640px;">
    <div class="alert alert-warning shadow-sm">
        <strong>Arşiv günlük dosyası yok.</strong> Gruplu günlük şu yolda aranır:
        <code class="d-block mt-2 small"><?= htmlspecialchars($archive, ENT_QUOTES, 'UTF-8'); ?></code>
        <p class="mb-0 mt-2 small">Tam XAMPP kurulumunda genelde <code>htdocs/changelog.php</code> (üst klasör) bulunur. Sunucuda yalnız <code>3.0.0/</code> dağıtıldıysa bu arşiv taşınmalı veya bu köprü güncellenmelidir.</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(esh_changelog_url(), ENT_QUOTES, 'UTF-8'); ?>">Sürüm günlüğüne dön</a>
</div>
</body>
</html>
    <?php
    exit;
}

require $archive;
