<?php
/**
 * Arşiv hasta listesi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $rows
 */
if (!empty($rows)) {
    foreach ($rows as $row): ?>
        <tr>
            <td class="text-muted small font-monospace"><?= htmlspecialchars((string) ($row->id ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="ps-4">
                <div class="fw-bold text-primary"><?= htmlspecialchars(trim($row->isim . ' ' . $row->soyisim), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-muted small"><i class="fa-solid fa-id-card me-1 opacity-50"></i><?= \App\Helpers\ValidationHelper::formatTc($row->tckimlik) ?></div>
            </td>
            <td>
                <div class="small fw-semibold"><?= htmlspecialchars((string) ($row->mahalleadi ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars((string) ($row->ilceadi ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td class="text-center">
                <span class="badge rounded-pill bg-info text-dark px-3 fw-bold">
                    <?= (int) ($row->izlemsayisi ?? 0) ?>
                </span>
            </td>
            <td>
                <?php if (!empty($row->sonizlemtarihi)): ?>
                    <div class="small fw-bold text-success"><?= date('d-m-Y', strtotime((string) $row->sonizlemtarihi)) ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;">Gerçekleşti</div>
                <?php else: ?>
                    <span class="text-muted small">Kayıt yok</span>
                <?php endif; ?>
            </td>
            <td class="text-end pe-4">
                <div class="btn-group btn-group-sm">
                    <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => (string) ($row->id ?? '')]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" title="Detaylar">
                        <i class="fa-solid fa-folder-open"></i>
                    </a>
                    <a href="<?= htmlspecialchars(esh_url('Patient', 'edit', ['id' => (string) ($row->id ?? '')]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary" title="Düzenle">
                        <i class="fa-solid fa-edit"></i>
                    </a>
                </div>
            </td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr>
        <td colspan="6" class="text-center py-5">
            <img src="assets/img/empty.svg" alt="" style="width: 100px; opacity: 0.3;" class="mb-3 d-block mx-auto">
            <p class="text-muted">Kriterlere uygun herhangi bir kayıt bulunamadı.</p>
        </td>
    </tr>
<?php } ?>
