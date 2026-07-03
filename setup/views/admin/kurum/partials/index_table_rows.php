<?php
/**
 * Kurum listesi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $items
 */
use App\Helpers\FederationHelper;

$eshKurumListShowBolge = FederationHelper::columnsReady();
$eshKurumListColspan = $eshKurumListShowBolge ? 7 : 6;
if (!empty($items)) {
    foreach ($items as $row): ?>
        <tr>
            <td><?= (int) ($row->id ?? 0) ?></td>
            <td><?= htmlspecialchars((string) ($row->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><code><?= htmlspecialchars((string) ($row->kod ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><?= htmlspecialchars((string) ($row->telefon ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <?php if ($eshKurumListShowBolge): ?>
            <td><?= htmlspecialchars(FederationHelper::kurumBolgeLabel(isset($row->bolge_id) ? (int) $row->bolge_id : null), ENT_QUOTES, 'UTF-8') ?></td>
            <?php endif; ?>
            <td>
                <?php if (!empty($row->aktif)): ?>
                    <span class="badge bg-success-subtle text-success border">Aktif</span>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border">Pasif</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <a href="<?= htmlspecialchars(esh_url('KurumAdres', 'index', ['kurum_id' => (int) $row->id]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm" title="Adres ataması">
                    <i class="fa-solid fa-map-location-dot"></i>
                </a>
                <a href="<?= htmlspecialchars(esh_url('Kurum', 'edit', ['id' => (int) $row->id]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-pen"></i>
                </a>
                <?php if ((int) ($row->id ?? 0) > 1): ?>
                    <form method="post" action="<?= htmlspecialchars(esh_url('Kurum', 'delete'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline" onsubmit="return confirm('Bu kurumu silmek istediğinize emin misiniz?');">
                        <?= esh_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $row->id ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr>
        <td colspan="<?= (int) $eshKurumListColspan ?>" class="text-center text-muted py-4">Henüz kurum tanımlı değil.</td>
    </tr>
<?php } ?>
