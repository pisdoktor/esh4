<?php
use App\Helpers\PageShellHelper;

$etken = $etken ?? null;
$ilaclar = is_array($ilaclar ?? null) ? $ilaclar : [];
$total = (int) ($total ?? 0);
$page = (int) ($page ?? 1);
$limit = (int) ($limit ?? 50);
$pagelink = (string) ($pagelink ?? '');
?>
<?php
PageShellHelper::pageOpen([
    'kind' => 'detail',
    'module' => 'ilac_rehber',
    'id' => 'esh-ilac-rehber-etken',
]);
PageShellHelper::pageHeader(
    (string) ($etken->ad ?? 'Etken madde'),
    'Bu etken maddeyi içeren ticari ürünler.',
    '<a href="' . htmlspecialchars(esh_url('IlacRehber', 'search'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Aramaya dön</a>',
    ['icon' => 'fa-solid fa-flask']
);
?>

<?php if (!empty($etken->scraped_at)): ?>
<p class="text-muted small mb-4">Kayıt tarihi: <?= htmlspecialchars(\App\Helpers\DateHelper::toTrDotDateTimeOrEmpty($etken->scraped_at ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<section class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold">İlaçlar (<?= (int) $total ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ürün adı</th>
                    <th class="text-end">Kaynak</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($ilaclar === []): ?>
                <tr>
                    <td colspan="2" class="text-center text-muted py-4">Bu etken için kayıtlı ilaç bulunamadı.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($ilaclar as $il): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($il->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                        <?php if (!empty($il->source_url)): ?>
                        <a href="<?= htmlspecialchars((string) $il->source_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">Detay</a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total > $limit): ?>
    <footer class="card-footer bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">
                <?= \App\Helpers\PaginationHelper::infoText($total, $page, $limit) ?>
            </div>
            <div>
                <?= \App\Helpers\PaginationHelper::render($total, $page, $limit, $pagelink) ?>
            </div>
        </div>
    </footer>
    <?php endif; ?>
</section>

<?php PageShellHelper::pageClose(); ?>
