<?php

declare(strict_types=1);

use App\Helpers\OperationalSettings;

$links = [];
if (OperationalSettings::isPublicHastaaramaEnabled()) {
    $links[] = [
        'href' => esh_url('PublicHastaarama', 'index', [], true),
        'label' => 'Kayıtlı hasta sorgulama (TC)',
    ];
}
if (OperationalSettings::isPatientPortalEnabled()) {
    $links[] = [
        'href' => esh_url('PatientPortal', 'login', [], true),
        'label' => 'Hasta / bakım veren portalı',
    ];
}

if ($links === []) {
    return;
}
?>
<p class="esh-login-hastaarama small text-center mt-3 mb-0">
    <?php foreach ($links as $i => $link): ?>
        <?php if ($i > 0): ?> · <?php endif; ?>
        <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></a>
    <?php endforeach; ?>
</p>
