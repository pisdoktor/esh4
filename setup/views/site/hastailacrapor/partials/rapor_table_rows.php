<?php
declare(strict_types=1);

use App\Helpers\AuthHelper;
use App\Helpers\DateHelper;

/** @var list<object> $rows */
/** @var list<array{raporlu:bool,badge:string,badgeText:string}> $rowMetas */
/** @var array<int, string> $bransById */
/** @var int $patientId */

if (empty($rows)): ?>
    <tr><td colspan="6" class="text-center text-muted py-4">Birleşik tanı listesi boş. Hasta kartında veya TC eşleşen kullanıcıda tanı id'leri tanımlayın.</td></tr>
<?php else:
    foreach ($rows as $i => $r):
        $hid = (int) ($r->hastalik_id ?? 0);
        $raporId = isset($r->rapor_id) && $r->rapor_id !== null && $r->rapor_id !== '' ? (int) $r->rapor_id : 0;
        $rowMeta = $rowMetas[$i] ?? ['raporlu' => false, 'badge' => 'secondary', 'badgeText' => 'Raporlu değil'];
        $raporlu = $rowMeta['raporlu'];
        $bitisTr = !empty($r->bitistarihi) ? DateHelper::toTrOrEmpty((string) $r->bitistarihi) : '';
        $brStr = trim((string) ($r->brans ?? ''));
        $brIds = $brStr !== '' ? array_filter(array_map('intval', explode(',', $brStr))) : [];
        $brLabels = [];
        foreach ($brIds as $bid) {
            if (!empty($bransById[$bid])) {
                $brLabels[] = $bransById[$bid];
            }
        }
        $ry = (int) ($r->raporyeri ?? 0);
        $ryTxt = $raporlu ? ($ry === 1 ? 'Bu kurum' : 'Dış merkez') : '—';
        $brCsv = implode(',', $brIds);
        ?>
    <tr class="hastailacrapor-rapor-row" data-raporlu="<?= $raporlu ? '1' : '0' ?>">
        <td class="fw-semibold"><?= htmlspecialchars((string) ($r->hastalikadi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><span class="badge bg-<?= htmlspecialchars($rowMeta['badge'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($rowMeta['badgeText'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><?= $bitisTr !== '' ? htmlspecialchars($bitisTr, ENT_QUOTES, 'UTF-8') : '—' ?></td>
        <td class="small"><?= $brLabels !== [] ? htmlspecialchars(implode(', ', $brLabels), ENT_QUOTES, 'UTF-8') : '—' ?></td>
        <td class="small"><?= htmlspecialchars($ryTxt, ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-end text-nowrap">
            <?php
            $__delBtn = '';
            if ($raporId > 0 && AuthHelper::sessionIsAdmin()) {
                $__delBtn = \App\Helpers\FormHelper::listActionButton([
                    'action' => esh_url('HastaIlacRapor', 'delete'),
                    'hidden' => ['id' => $raporId, 'return' => $patientId],
                    'title' => 'Sil',
                    'icon' => 'fa-solid fa-trash',
                    'variant' => 'danger',
                    'confirm' => 'Bu rapor satırını kalıcı olarak silmek istediğinize emin misiniz?',
                ]);
            }
            ?>
            <?= \App\Helpers\FormHelper::listActionsGroup(
                '<button type="button" class="btn btn-sm btn-outline-primary hastailacrapor-edit-rapor-btn" data-bs-toggle="modal" data-bs-target="#hastailacRaporModal" title="Düzenle"'
                . ' data-hastalik-id="' . $hid . '"'
                . ' data-hastalik-adi="' . htmlspecialchars((string) ($r->hastalikadi ?? ''), ENT_QUOTES, 'UTF-8') . '"'
                . ' data-rapor-id="' . $raporId . '"'
                . ' data-rapor="' . ($raporlu ? '1' : '0') . '"'
                . ' data-bitis="' . htmlspecialchars($bitisTr, ENT_QUOTES, 'UTF-8') . '"'
                . ' data-brans="' . htmlspecialchars($brCsv, ENT_QUOTES, 'UTF-8') . '"'
                . ' data-raporyeri="' . $ry . '"'
                . ' data-status-badge="' . htmlspecialchars($rowMeta['badge'], ENT_QUOTES, 'UTF-8') . '"'
                . ' data-status-text="' . htmlspecialchars($rowMeta['badgeText'], ENT_QUOTES, 'UTF-8') . '"'
                . '><i class="fa-solid fa-pen" aria-hidden="true"></i></button>' . $__delBtn
            ) ?>
            <?php unset($__delBtn); ?>
        </td>
    </tr>
    <?php endforeach;
endif;
