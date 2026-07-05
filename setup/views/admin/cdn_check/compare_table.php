<?php
/**
 * @var list<array<string, mixed>> $compareRows
 */
use App\Helpers\CdnAssetHelper;

$compareRows = $compareRows ?? [];
?>
<div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th scope="col">Kütüphane</th>
                <th scope="col">Sabitlenmiş</th>
                <th scope="col">Son sürüm</th>
                <th scope="col">Kayıt defteri</th>
                <th scope="col" class="text-end">Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($compareRows === []): ?>
                <tr>
                    <td colspan="5" class="text-muted text-center py-4">Karşılaştırılacak kayıt bulunamadı.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($compareRows as $row): ?>
                    <?php
                    $const = (string) ($row['const'] ?? '');
                    $pinned = (string) ($row['pinned'] ?? '');
                    $latest = $row['latest'] ?? null;
                    $latestText = is_string($latest) && $latest !== '' ? $latest : null;
                    $registry = (string) ($row['registry'] ?? '');
                    $registryId = $row['id'] ?? null;
                    $meta = CdnAssetHelper::versionCompareRowMeta($row);
                    ?>
                    <tr<?= $meta['rowClass'] !== '' ? ' class="' . htmlspecialchars($meta['rowClass'], ENT_QUOTES, 'UTF-8') . '"' : '' ?> title="<?= htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8') ?>">
                        <td>
                            <span class="fw-semibold"><?= htmlspecialchars(CdnAssetHelper::formatVersionCompareConstLabel($const), ENT_QUOTES, 'UTF-8') ?></span>
                            <code class="d-block small text-muted mt-1"><?= htmlspecialchars($const, ENT_QUOTES, 'UTF-8') ?></code>
                        </td>
                        <td><code><?= htmlspecialchars($pinned !== '' ? $pinned : '—', ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td>
                            <?php if ($latestText !== null): ?>
                                <code class="<?= ($row['newer'] ?? null) === true ? 'fw-semibold' : '' ?>"><?= htmlspecialchars($latestText, ENT_QUOTES, 'UTF-8') ?></code>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($registry === 'manual'): ?>
                                <span class="badge text-bg-secondary">Manuel</span>
                            <?php else: ?>
                                <?php
                                $regLabel = $registry;
                                if (is_string($registryId) && $registryId !== '') {
                                    $regLabel .= '/' . $registryId;
                                }
                                ?>
                                <span class="badge text-bg-light border text-dark fw-normal"><code class="small"><?= htmlspecialchars($regLabel, ENT_QUOTES, 'UTF-8') ?></code></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <span class="badge text-bg-<?= htmlspecialchars($meta['badge'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<p class="small text-muted mb-0 px-3 py-2 border-top bg-light">
    <span class="badge text-bg-success me-1">Güncel</span> sabitlenmiş sürüm uyumlu
    · <span class="badge text-bg-warning me-1">Yeni sürüm</span> kayıt defterinde daha yeni
    · <span class="badge text-bg-secondary me-1">Manuel</span> uzaktan karşılaştırılmıyor
    · <span class="badge text-bg-warning me-1">Belirsiz</span> son sürüm okunamadı
</p>