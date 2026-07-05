<?php
declare(strict_types=1);

/** @var list<object> $items */
$items = $items ?? [];
?>
<div class="esh-page esh-page--list container-fluid py-4">
    <header class="esh-page__header mb-4 d-flex justify-content-between align-items-center">
        <h1 class="h4 mb-0"><i class="fa-solid fa-map me-2"></i>Federasyon bölgeleri</h1>
        <a href="<?= htmlspecialchars(esh_url('FederationRegion', 'create'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm rounded-pill">
            <i class="fa-solid fa-plus me-1"></i>Yeni bölge
        </a>
    </header>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ad</th>
                        <th>Kod</th>
                        <th>İl</th>
                        <th>Hub ref</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Henüz bölge yok.</td></tr>
                    <?php else: foreach ($items as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($row->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code><?= htmlspecialchars((string) ($row->kod ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= htmlspecialchars((string) ($row->il_adi ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small font-monospace"><?= htmlspecialchars((string) ($row->hub_node_ref ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (!empty($row->aktif)): ?>
                                <span class="badge text-bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= htmlspecialchars(esh_url('FederationRegion', 'edit', ['id' => (int) $row->id]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-pen"></i></a>
                            <?php if ((int) ($row->id ?? 0) > 1): ?>
                            <form method="post" action="<?= htmlspecialchars(esh_url('FederationRegion', 'delete'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline" data-esh-confirm="Silinsin mi?">
                                <?= esh_csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $row->id ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
