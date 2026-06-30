<?php
/**
 * EK-3 başvuru amaçları tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $items
 */
if (!empty($items)) {
    foreach ($items as $row): ?>
        <tr>
            <td><?= (int) $row->id ?></td>
            <td><?= htmlspecialchars((string) ($row->istek_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <?= \App\Helpers\FormHelper::listActionsCell([
                [
                    'href' => esh_url('Istek', 'edit', array (
  'id' => '',
)) . (int) $row->id,
                    'title' => 'Düzenle',
                    'icon' => 'fas fa-edit',
                    'variant' => 'primary',
                ],
                [
                    'action' => esh_url('Istek', 'delete'),
                    'hidden' => ['id' => (int) $row->id],
                    'title' => 'Sil',
                    'icon' => 'fas fa-trash',
                    'variant' => 'danger',
                    'confirm' => 'Bu başvuru amacını silmek istediğinize emin misiniz? İzlem kayıtlarındaki eski seçimler metin olarak kalabilir.',
                ],
            ], ['align' => 'center', 'smallTd' => false]) ?>
        </tr>
    <?php endforeach;
} else { ?>
    <tr><td colspan="3" class="text-center py-4 text-muted">Kayıt yok. Yeni başvuru amacı ekleyin.</td></tr>
<?php } ?>
