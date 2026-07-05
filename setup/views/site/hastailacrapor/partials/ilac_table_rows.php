<?php
declare(strict_types=1);

/** @var list<object> $ilaclar */
/** @var array<string, string> $hastalikAdByIcd */
/** @var string $patientId */

if (empty($ilaclar)): ?>
    <tr><td colspan="4" class="text-center text-muted py-4">Henüz ilaç kaydı yok. Yukarıdaki formdan ekleyebilirsiniz.</td></tr>
<?php else:
    foreach ($ilaclar as $il):
        $ilId = (string) ($il->id ?? '');
        $ilIcd = \App\Models\Patient::normalizeHastalikIcd((string) ($il->hastalikicd ?? ''));
        $ilTani = $ilIcd !== '' ? ($hastalikAdByIcd[$ilIcd] ?? $ilIcd) : '—';
        $ilNot = trim((string) ($il->not ?? ''));
        $ilRecete = trim((string) ($il->recete_turu ?? ''));
        $ilMetaParts = $ilRecete !== '' ? [$ilRecete] : [];
        ?>
    <tr>
        <td>
            <span class="fw-semibold d-block"><?= htmlspecialchars((string) ($il->ilac_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($ilMetaParts !== []): ?>
                <span class="small text-muted"><?= htmlspecialchars(implode(' · ', $ilMetaParts), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </td>
        <td class="small"><?= $ilNot !== '' ? htmlspecialchars($ilNot, ENT_QUOTES, 'UTF-8') : '—' ?></td>
        <td class="small"><?= htmlspecialchars($ilTani, ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-end text-nowrap">
            <?php
            $__ilDel = \App\Helpers\FormHelper::listActionButton([
                'action' => esh_url('HastaIlacRapor', 'deleteIlac'),
                'hidden' => ['id' => $ilId, 'return' => $patientId],
                'title' => 'Sil',
                'icon' => 'fa-solid fa-trash',
                'variant' => 'danger',
                'confirm' => 'Bu ilaç kaydını silmek istediğinize emin misiniz?',
            ]);
            ?>
            <?= \App\Helpers\FormHelper::listActionsGroup(
                '<button type="button" class="btn btn-sm btn-outline-primary hastailacrapor-edit-ilac-btn" data-bs-toggle="modal" data-bs-target="#hastailacIlacModal" title="Düzenle"'
                . ' data-ilac-id="' . htmlspecialchars($ilId, ENT_QUOTES, 'UTF-8') . '"'
                . ' data-ilac-adi="' . htmlspecialchars((string) ($il->ilac_adi ?? ''), ENT_QUOTES, 'UTF-8') . '"'
                . ' data-recete-turu="' . htmlspecialchars($ilRecete, ENT_QUOTES, 'UTF-8') . '"'
                . ' data-not="' . htmlspecialchars($ilNot, ENT_QUOTES, 'UTF-8') . '"'
                . ' data-hastalik-icd="' . htmlspecialchars($ilIcd, ENT_QUOTES, 'UTF-8') . '"'
                . '><i class="fa-solid fa-pen" aria-hidden="true"></i></button>' . $__ilDel
            ) ?>
            <?php unset($__ilDel); ?>
        </td>
    </tr>
    <?php endforeach;
endif;
