<?php
use App\Helpers\StatsNavHelper;

$rows = [];
foreach (StatsNavHelper::hubGroups() as $group) {
    foreach (StatsNavHelper::hubGroupCardsFlat($group) as $card) {
        $rows[] = [$card['action'], $card['title']];
    }
}
?>
<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-header py-2">
            <strong>İstatistik Merkezi (Old)</strong>
        </div>
        <div class="card-body py-2">
            <p class="small text-muted mb-2">Eski sistem tarzı sade liste görünümü — sıra istatistik merkezi gruplarıyla uyumludur.</p>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>Rapor</th>
                            <th style="width:140px;" class="text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $i => $r): ?>
                            <tr>
                                <td><?= (int) ($i + 1) ?></td>
                                <td><?= htmlspecialchars((string) $r[1], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(esh_url('Stats', (string) $r[0]), ENT_QUOTES, 'UTF-8') ?>">Aç</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
