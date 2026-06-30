<?php
/** @var list<object> $assignments */
$tipLabels = ['ilce' => 'İlçe', 'mahalle' => 'Mahalle', 'sokak' => 'Sokak'];
if (empty($assignments)): ?>
<tr class="esh-kurum-adres-empty"><td colspan="4" class="text-center text-muted py-4">Bu kuruma henüz adres atanmamış.</td></tr>
<?php else: ?>
<?php foreach ($assignments as $row): ?>
<tr>
    <td>
        <span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($tipLabels[(string) ($row->tip ?? '')] ?? (string) ($row->tip ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
    </td>
    <td><?= htmlspecialchars((string) ($row->adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    <td class="small text-muted"><?= htmlspecialchars((string) ($row->yol ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    <td class="text-center">
        <button type="button" class="btn btn-outline-danger btn-sm esh-kurum-adres-unassign"
                data-adres-id="<?= htmlspecialchars((string) ($row->adres_id ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                title="Atamayı kaldır">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
