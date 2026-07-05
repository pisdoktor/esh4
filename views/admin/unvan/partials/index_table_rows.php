<?php
/**
 * Personel ünvanları tablo satırları.
 * @var list<object> $items
 * @var array<string, string> $kategoriLabels
 */
$kategoriLabels = $kategoriLabels ?? \App\Models\Unvan::kategoriChoices();
if (!empty($items)) {
    foreach ($items as $row):
        $kod = (string) ($row->kod ?? '');
        $kat = (string) ($row->kategori ?? '');
        $katLabel = $kategoriLabels[$kat] ?? $kat;
        $aktif = (int) ($row->aktif ?? 0) === 1;
        $isSystem = (int) ($row->is_system ?? 0) === 1;
        ?>
        <tr class="<?= $aktif ? '' : 'table-secondary' ?>">
            <td><?= (int) $row->id ?></td>
            <td><code><?= htmlspecialchars($kod, ENT_QUOTES, 'UTF-8') ?></code></td>
            <td>
                <span class="fw-bold"><?= htmlspecialchars((string) ($row->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($isSystem): ?>
                    <span class="badge bg-light text-muted border ms-1">sistem</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($katLabel, ENT_QUOTES, 'UTF-8') ?></td>
            <td><code><?= htmlspecialchars((string) ($row->izin_sablonu ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
            <td class="text-center">
                <?php if ($aktif): ?>
                    <span class="badge bg-success-subtle text-success border">Aktif</span>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border">Pasif</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <a href="<?= htmlspecialchars(esh_url('Unvan', 'edit', ['id' => (int) $row->id]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" title="Düzenle">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form method="post" action="<?= htmlspecialchars(esh_url('Unvan', 'delete'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" data-esh-confirm="Bu ünvanı silmek veya pasifleştirmek istediğinize emin misiniz?">
                        <?= \App\Helpers\CsrfHelper::hiddenField() ?>
                        <input type="hidden" name="id" value="<?= (int) $row->id ?>">
                        <button type="submit" class="btn btn-outline-danger" title="<?= $isSystem ? 'Pasifleştir' : 'Sil' ?>">
                            <i class="fas fa-<?= $isSystem ? 'ban' : 'trash' ?>"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr>
        <td colspan="7" class="text-center py-4 text-muted">Kayıtlı ünvan bulunamadı. Kurulum seed dosyalarının yüklendiğinden emin olun.</td>
    </tr>
<?php } ?>
