<?php
declare(strict_types=1);

/** @var list<array<string, mixed>> $themesMeta */
/** @var string $siteThemeSlug */

if (empty($themesMeta)): ?>
    <tr><td colspan="8" class="text-center text-muted py-4">Tanımlı tema bulunamadı.</td></tr>
<?php else:
    foreach ($themesMeta as $row):
        $slug = (string) ($row['slug'] ?? '');
        ?>
    <tr>
        <td class="fw-semibold"><?= htmlspecialchars((string) ($row['name'] ?? $slug), ENT_QUOTES, 'UTF-8') ?></td>
        <td><code><?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></code></td>
        <td class="small">
            <?php
            $surumStr = trim((string) ($row['surum'] ?? ''));
            if ($surumStr !== '') {
                echo '<span class="badge bg-secondary-subtle text-secondary border">' . htmlspecialchars($surumStr, ENT_QUOTES, 'UTF-8') . '</span>';
            } else {
                echo '<span class="text-muted">—</span>';
            }
            ?>
        </td>
        <td class="text-muted small"><?= htmlspecialchars((string) ($row['olusturulma_tarihi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-muted small"><?= htmlspecialchars((string) ($row['guncelleme_tarihi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="small"><?= htmlspecialchars((string) ($row['olusturan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-center">
            <?php if ($slug === ($siteThemeSlug ?? '')): ?>
                <span class="badge bg-success">Site varsayılanı</span>
            <?php else: ?>
                <span class="badge bg-secondary">—</span>
            <?php endif; ?>
        </td>
        <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary me-1" href="<?= htmlspecialchars(\App\Helpers\ThemeViewHelper::editorPageUrl($slug), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" title="Tema editörü (yeni pencere)">
                <i class="fa-solid fa-sliders" title="Tema editörü"></i>
            </a>
            <?php if ($slug === ($siteThemeSlug ?? '')): ?>
                <button class="btn btn-sm btn-outline-success" disabled>Seçili</button>
            <?php else: ?>
                <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars(esh_url('Theme', 'activate', ['theme' => urlencode($slug)]), ENT_QUOTES, 'UTF-8') ?>">Aktif yap</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach;
endif;
