<?php
declare(strict_types=1);
/** @var array<int, array<string, string>> $themesMeta \App\Helpers\ThemeViewHelper::discoverThemesMeta() */
/** @var string|null $userUiTheme DB ui_theme veya null */
use App\Helpers\ThemeViewHelper;

$themesMeta = $themesMeta ?? [];
$userUiTheme = isset($userUiTheme) ? $userUiTheme : null;
$siteSlug = ThemeViewHelper::siteThemeSlug();
$stored = $userUiTheme !== null ? trim((string) $userUiTheme) : '';
$savedSlug = $stored !== '' ? ThemeViewHelper::sanitizeThemeSlug($stored) : '';
if ($stored !== '' && !ThemeViewHelper::isInstalledThemeSlug($savedSlug)) {
    $savedSlug = '';
}
?>
<div class="col-12 mt-3">
    <label class="form-label small fw-semibold"><i class="fa-solid fa-palette me-1 text-secondary"></i>Arayüz teması</label>
    <select name="ui_theme" id="esh-ui-theme-select" class="form-select">
        <option value="__site__" <?= $savedSlug === '' ? ' selected' : '' ?>><?= htmlspecialchars('Site varsayılanı (' . $siteSlug . ')', ENT_QUOTES, 'UTF-8') ?></option>
        <?php foreach ($themesMeta as $row) : ?>
            <?php
            $slug = strtolower(trim((string) ($row['slug'] ?? '')));
            if ($slug === '' || !ThemeViewHelper::isInstalledThemeSlug($slug)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? $slug));
            $label = $name . ' (' . $slug . ')';
            $selected = ($savedSlug !== '' && $savedSlug === $slug) ? ' selected' : '';
            ?>
            <option value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"<?= $selected ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
    <div class="form-text small text-muted">Boş / site varsayılanı: Yönetici <code>active_theme</code> ayarı. Kendi seçiminiz yalnızca sizin oturumunuza uygulanır.</div>
</div>
