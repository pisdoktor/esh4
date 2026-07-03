<?php
declare(strict_types=1);
/** @var string $eshGuestError */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(esh_app_name(), ENT_QUOTES, 'UTF-8'); ?> · Görüntülü görüşme</title>
    <?= esh_version_meta_tag(); ?>
    <?= \App\Helpers\CdnAssetHelper::minimalAuthLayoutStylesHtml() ?>
</head>
<body class="p-4" style="font-family: system-ui, sans-serif; max-width: 32rem; margin: auto;">
    <h1 class="h4">Görüntülü görüşme</h1>
    <p class="text-danger"><?= htmlspecialchars((string) ($eshGuestError ?? 'Erişim reddedildi.'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><a href="<?= htmlspecialchars(esh_url('Auth', 'login', [], true), ENT_QUOTES, 'UTF-8') ?>">Personel girişi</a></p>
</body>
</html>
