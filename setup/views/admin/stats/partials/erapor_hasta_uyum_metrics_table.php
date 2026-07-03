<?php
/**
 * e-Rapor ↔ hasta uyum — metrik ve sayı tablosu (gruplu).
 *
 * @var list<array{key: string, label: string, group: string, group_hint?: string, listable: bool, kind: string, count: int}> $metricsRows
 * @var string|null $activeMetric Seçili metrik (liste sayfasında vurgulanır)
 * @var bool $compact Kompakt görünüm (liste sayfası üstü)
 */
$metricsRows = $metricsRows ?? [];
$activeMetric = isset($activeMetric) ? (string) $activeMetric : '';
$compact = !empty($compact);
$listBase = ['controller' => 'Stats', 'action' => 'eraporHastaUyumList'];

$kindBadge = static function (string $kind): string {
    return match ($kind) {
        'ok' => 'success',
        'warn' => 'danger',
        'info' => 'secondary',
        default => 'warning',
    };
};

$currentGroup = null;
$colCount = 4;
?>
<div class="<?= $compact ? 'mb-3' : 'mb-4' ?>">
    <?php if (!$compact): ?>
        <h6 class="fw-bold text-dark mb-3">
            <i class="fa-solid fa-table-list me-2 text-info"></i>Metrik özeti — tüm sayılar
        </h6>
    <?php else: ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <span class="fw-semibold small text-secondary text-uppercase">Metrik sayıları</span>
            <a href="<?= htmlspecialchars(esh_url('Stats', 'eraporHastaUyum'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm py-0">Özet sayfası</a>
        </div>
    <?php endif; ?>
    <div class="table-responsive border rounded-3">
        <table class="table table-sm table-hover mb-0 align-middle<?= $compact ? ' small' : '' ?>">
            <thead class="table-light">
                <tr>
                    <th scope="col" style="width:<?= $compact ? '30%' : '24%' ?>">Metrik</th>
                    <th scope="col">Açıklama</th>
                    <th scope="col" class="text-end" style="width:72px">Sayı</th>
                    <th scope="col" class="text-end" style="width:<?= $compact ? '72px' : '88px' ?>">Liste</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($metricsRows as $row): ?>
                    <?php
                    $groupLabel = (string) ($row['group'] ?? '');
                    $groupHint = (string) ($row['group_hint'] ?? '');
                    if ($groupLabel !== '' && $groupLabel !== $currentGroup):
                        $currentGroup = $groupLabel;
                        ?>
                    <tr class="table-secondary">
                        <td colspan="<?= $colCount ?>" class="py-2<?= $compact ? ' small' : '' ?>">
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($groupHint !== ''): ?>
                                <span class="text-muted fw-normal ms-2">— <?= htmlspecialchars($groupHint, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php
                    $key = (string) ($row['key'] ?? '');
                    $count = (int) ($row['count'] ?? 0);
                    $listable = !empty($row['listable']);
                    $isActive = $activeMetric !== '' && $key === $activeMetric;
                    $listUrl = ($listable && $count > 0)
                        ? \App\Helpers\UrlHelper::fromRequestParams(array_merge($listBase, ['metric' => $key]))
                        : null;
                    ?>
                    <tr class="<?= $isActive ? 'table-info' : ($count > 0 && ($row['kind'] ?? '') === 'issue' ? 'table-warning' : '') ?>">
                        <td>
                            <code class="user-select-all<?= $isActive ? ' fw-bold' : '' ?>"><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></code>
                            <?php if ($isActive): ?>
                                <span class="badge bg-info text-dark ms-1">seçili</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string) ($row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <span class="badge bg-<?= $kindBadge((string) ($row['kind'] ?? 'issue')) ?>"><?= $count ?></span>
                        </td>
                        <td class="text-end">
                            <?php if ($listUrl !== null): ?>
                                <a href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm py-0">Gör</a>
                            <?php elseif (!$listable): ?>
                                <span class="badge text-bg-light border text-secondary"
                                      title="Özet metrik: satır listesi yok; aynı konudaki uyumsuzluk satırından Gör ile detaya gidin.">Özet</span>
                            <?php else: ?>
                                <span class="text-muted" title="Bu metrikte kayıt yok">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
