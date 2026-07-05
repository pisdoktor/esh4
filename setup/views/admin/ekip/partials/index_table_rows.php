<?php
/**
 * Ekip günlük plan tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $items
 */
$ekipModel = new \App\Models\Ekip();

if (!empty($items)) {
    foreach ($items as $row) {
        $p_ozet = $ekipModel->getPersonnelPreviewForDate($row->tarih);
        $tarih_tr = \App\Helpers\DateHelper::toTrDotOrEmpty($row->tarih);
        $tarihUrl = htmlspecialchars((string) $row->tarih, ENT_QUOTES, 'UTF-8');
        ?>
        <tr>
            <td><strong><?= htmlspecialchars($tarih_tr, ENT_QUOTES, 'UTF-8'); ?></strong></td>
            <td><span class="badge bg-info text-dark"><?= (int) $row->ekip_sayisi; ?> Ekip</span></td>
            <td><small><?= htmlspecialchars((string) ($row->saatler ?? ''), ENT_QUOTES, 'UTF-8'); ?></small></td>
            <td><small class="text-muted"><?= htmlspecialchars($p_ozet, ENT_QUOTES, 'UTF-8'); ?></small></td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <a href="<?= htmlspecialchars(esh_url('Ekip', 'edit', ['tarih' => $tarihUrl]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-primary" title="Düzenle"><i class="fa fa-edit"></i></a>
                    <form method="post" action="<?= htmlspecialchars(esh_url('Ekip', 'deleteDay'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" data-esh-confirm="Bu tarihteki TÜM ekipler silinsin mi?">
                        <input type="hidden" name="tarih" value="<?= htmlspecialchars($tarihUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-danger" title="Sil"><i class="fa fa-trash"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        <?php
    }
} else {
    echo '<tr><td colspan="5" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>';
}
