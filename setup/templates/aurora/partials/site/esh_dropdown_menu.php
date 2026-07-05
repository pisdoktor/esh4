<?php
declare(strict_types=1);
/**
 * Hasta adı / Tıbbi İşlemler — mega dropdown (İzlem | Formlar | Hasta [| Yönetim]).
 *
 * @var list<array<string, mixed>> $eshMenuRows
 */
if (empty($eshMenuRows) || !is_array($eshMenuRows)) {
    return;
}

$mega = \App\Helpers\BadgeHelper::menuRowsToMegaColumns($eshMenuRows);
$eshMegaStatus = $mega['status'] ?? null;
$eshMegaVisitTitle = (string) ($mega['visitTitle'] ?? 'İzlem işlemleri');
$eshMegaFormsTitle = (string) ($mega['formsTitle'] ?? 'Formlar');
$eshMegaPatientTitle = (string) ($mega['patientTitle'] ?? 'Hasta işlemleri');
$eshMegaAdminTitle = (string) ($mega['adminTitle'] ?? 'Hasta yönetimi');
$eshMegaVisitItems = $mega['visit'] ?? [];
$eshMegaFormsItems = $mega['forms'] ?? [];
$eshMegaPatientItems = $mega['patient'] ?? [];
$eshMegaAdminItems = $mega['admin'] ?? [];
$eshMegaHasForms = $eshMegaFormsItems !== [];
$eshMegaHasAdmin = $eshMegaAdminItems !== [];
$eshMegaColCount = 2 + ($eshMegaHasForms ? 1 : 0) + ($eshMegaHasAdmin ? 1 : 0);
$eshMegaColClass = match ($eshMegaColCount) {
    4 => 'col-3',
    3 => 'col-4',
    default => 'col-6',
};
$eshMegaMenuMods = [];
if ($eshMegaHasForms) {
    $eshMegaMenuMods[] = 'esh-patient-mega-menu--with-forms';
}
if ($eshMegaHasAdmin) {
    $eshMegaMenuMods[] = 'esh-patient-mega-menu--with-admin';
}

$eshRenderMegaItem = static function (array $mrow): void {
    ?>
    <a class="dropdown-item d-flex align-items-center py-2<?= !empty($mrow['danger']) ? ' text-danger' : '' ?>"
       href="<?= htmlspecialchars((string) ($mrow['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
       <?php if (!empty($mrow['confirm'])): ?>data-esh-confirm="<?= htmlspecialchars((string) $mrow['confirm'], ENT_QUOTES, 'UTF-8') ?>" <?php endif; ?>>
        <i class="<?= htmlspecialchars((string) ($mrow['icon'] ?? 'fa-solid fa-link'), ENT_QUOTES, 'UTF-8') ?> me-2"></i>
        <?= htmlspecialchars((string) ($mrow['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </a>
    <?php
};
?>
<div class="dropdown-menu esh-patient-mega-menu esh-patient-mega-menu--fade shadow-lg border-0 p-2 custom-dropdown<?= $eshMegaMenuMods !== [] ? ' ' . implode(' ', $eshMegaMenuMods) : '' ?>" data-esh-mega-cols="<?= (int) $eshMegaColCount ?>">
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
        <?php if ($eshMegaHasForms): ?>
        <div class="<?= $eshMegaColClass ?>">
            <div class="esh-patient-mega-menu__col h-100 esh-patient-mega-menu__col--forms">
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($eshMegaFormsTitle, ENT_QUOTES, 'UTF-8') ?></h6>
                <?php foreach ($eshMegaFormsItems as $mrow): ?>
                    <?php $eshRenderMegaItem($mrow); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
