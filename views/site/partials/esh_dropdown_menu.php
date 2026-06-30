<?php
declare(strict_types=1);
/**
 * Hasta adı / Tıbbi İşlemler — mega dropdown (İzlem | Hasta [| Yönetim]).
 *
 * @var list<array<string, mixed>> $eshMenuRows
 */
if (empty($eshMenuRows) || !is_array($eshMenuRows)) {
    return;
}

$mega = \App\Helpers\BadgeHelper::menuRowsToMegaColumns($eshMenuRows);
$eshMegaStatus = $mega['status'] ?? null;
$eshMegaVisitTitle = (string) ($mega['visitTitle'] ?? 'İzlem işlemleri');
$eshMegaPatientTitle = (string) ($mega['patientTitle'] ?? 'Hasta işlemleri');
$eshMegaAdminTitle = (string) ($mega['adminTitle'] ?? 'Hasta yönetimi');
$eshMegaVisitItems = $mega['visit'] ?? [];
$eshMegaPatientItems = $mega['patient'] ?? [];
$eshMegaAdminItems = $mega['admin'] ?? [];
$eshMegaHasAdmin = $eshMegaAdminItems !== [];
$eshMegaColClass = $eshMegaHasAdmin ? 'col-4' : 'col-6';

$eshRenderMegaItem = static function (array $mrow): void {
    ?>
    <a class="dropdown-item d-flex align-items-center py-2<?= !empty($mrow['danger']) ? ' text-danger' : '' ?>"
       href="<?= htmlspecialchars((string) ($mrow['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
       <?php if (!empty($mrow['confirm'])): ?>onclick='return confirm(<?= json_encode((string) $mrow['confirm'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);'<?php endif; ?>>
        <i class="<?= htmlspecialchars((string) ($mrow['icon'] ?? 'fa-solid fa-link'), ENT_QUOTES, 'UTF-8') ?> me-2"></i>
        <?= htmlspecialchars((string) ($mrow['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </a>
    <?php
};
?>
<div class="dropdown-menu esh-patient-mega-menu esh-patient-mega-menu--fade shadow-lg border-0 p-2 custom-dropdown<?= $eshMegaHasAdmin ? ' esh-patient-mega-menu--with-admin' : '' ?>">
    <?php if ($eshMegaStatus !== null && $eshMegaStatus !== ''): ?>
        <div class="esh-patient-mega-menu__head small text-muted px-2 pb-2 mb-2 border-bottom"><?= htmlspecialchars($eshMegaStatus, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <div class="row g-2 mx-0 esh-patient-mega-menu__cols">
        <div class="<?= $eshMegaColClass ?>">
            <div class="esh-patient-mega-menu__col h-100">
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($eshMegaVisitTitle, ENT_QUOTES, 'UTF-8') ?></h6>
                <?php if ($eshMegaVisitItems === []): ?>
                    <span class="dropdown-item-text small text-muted py-1 px-2">—</span>
                <?php else: ?>
                    <?php foreach ($eshMegaVisitItems as $mrow): ?>
                        <?php $eshRenderMegaItem($mrow); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="<?= $eshMegaColClass ?>">
            <div class="esh-patient-mega-menu__col h-100">
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($eshMegaPatientTitle, ENT_QUOTES, 'UTF-8') ?></h6>
                <?php if ($eshMegaPatientItems === []): ?>
                    <span class="dropdown-item-text small text-muted py-1 px-2">—</span>
                <?php else: ?>
                    <?php foreach ($eshMegaPatientItems as $mrow): ?>
                        <?php $eshRenderMegaItem($mrow); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($eshMegaHasAdmin): ?>
        <div class="<?= $eshMegaColClass ?>">
            <div class="esh-patient-mega-menu__col h-100 esh-patient-mega-menu__col--admin">
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($eshMegaAdminTitle, ENT_QUOTES, 'UTF-8') ?></h6>
                <?php foreach ($eshMegaAdminItems as $mrow): ?>
                    <?php $eshRenderMegaItem($mrow); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
