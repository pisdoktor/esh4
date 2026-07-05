<?php
declare(strict_types=1);

/** @var list<object> $rows */

if (empty($rows)): ?>
    <tr><td colspan="6" class="text-center text-muted py-5">Bugün doğum günü olan aktif hasta yok.</td></tr>
<?php else:
    foreach ($rows as $row):
        $erkek = \App\Helpers\CinsiyetHelper::isErkek($row->cinsiyet ?? null);
        $renk = $erkek ? '#0d6efd' : '#dc3545';
        $yas = \App\Helpers\DateHelper::calculateAge($row->dogumtarihi ?? '');
        ?>
    <tr>
        <td class="text-center" style="width: 76px;">
            <?= \App\Helpers\UIHelper::patientSummaryButtons(
                (string) ($row->tckimlik ?? ''),
                (int) ($row->izlemsayisi ?? 0),
                (int) ($row->yizlemsayisi ?? 0),
                (int) ($row->totalplanli ?? 0)
            ) ?>
        </td>
        <td class="ps-4">
            <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => (string) ($row->id ?? '')]), ENT_QUOTES, 'UTF-8') ?>" class="fw-semibold text-decoration-none" style="color:<?= htmlspecialchars($renk, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) $row->isim, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) $row->soyisim, ENT_QUOTES, 'UTF-8') ?>
            </a>
        </td>
        <td class="small text-muted"><?= \App\Helpers\ValidationHelper::formatTc((string) $row->tckimlik) ?></td>
        <td>
            <span class="small d-block"><?= htmlspecialchars((string) ($row->mahalle ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge bg-success-subtle text-success"><?= htmlspecialchars((string) ($row->ilce ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </td>
        <td><small><?= htmlspecialchars(\App\Helpers\DateHelper::toTrOrEmpty($row->dogumtarihi ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
        <td class="text-center"><span class="badge bg-primary rounded-pill"><?= htmlspecialchars((string) $yas, ENT_QUOTES, 'UTF-8') ?></span></td>
    </tr>
    <?php endforeach;
endif;
