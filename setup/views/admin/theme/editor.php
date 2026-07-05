<?php
/** @var list<array{slug:string,name:string,surum:string}> $themesMeta */
/** @var string $editorThemeSlug */
/** @var list<array{name:string,value:string,line:int,picker_hex:?string,kind:string,label:string,group:string}> $colorTokens */
/** @var array<string, list<array<string, mixed>>> $colorTokenGroups */
/** @var list<array{id:string,type:string,name:string,value:string,line:int,label:string}> $gradientVarTokens */
/** @var list<array{id:string,type:string,selector:string,property:string,value:string,line:int,label:string}> $gradientPropEntries */
/** @var list<array{id:string,type:string,name:string,value:string,line:int,label:string,hint:string}> $otherVarTokens */
/** @var list<array{id:string,type:string,selector:string,property:string,value:string,line:int,label:string,hint:string}> $otherPropEntries */
/** @var list<array{id:string,type:string,name:string,value:string,line:int,label:string,hint:string}> $typographyVarTokens */
/** @var list<array{id:string,type:string,selector:string,property:string,value:string,line:int,label:string,hint:string}> $typographyPropEntries */
/** @var array<string, list<array<string, mixed>>> $eshUiTokenGroups */
/** @var list<array{id:string,type:string,name:string,value:string,line:int,label:string,group:string,hint:string,missing_in_file:bool,picker_hex:?string}> $eshUiTokens */
/** @var list<array{id:string,type:string,name:string,value:string,line:int,label:string,hint:string,missing_in_file:bool,picker_hex:?string}> $eshUiTypographyTokens */
/** @var bool $eshUiBridgePresent */
/** @var string $eshUiBridgeSuggestion */
/** @var string $previewBodyClasses */
/** @var list<string> $previewStylesheetUrls */
/** @var array|null $sessionPreview */
/** @var bool $sessionPreviewActive */
/** @var bool $editorStandalone */
$editorStandalone = !empty($editorStandalone);
$hasGradients = $gradientVarTokens !== [] || $gradientPropEntries !== [];
$hasOther = $otherVarTokens !== [] || $otherPropEntries !== [];
$hasTypography = $eshUiTypographyTokens !== [] || $typographyVarTokens !== [] || $typographyPropEntries !== [];
$hasEshUi = $eshUiTokens !== [];
$eshUiMissingCount = 0;
foreach ($eshUiTokens as $eshUiRow) {
    if (!empty($eshUiRow['missing_in_file'])) {
        $eshUiMissingCount++;
    }
}
$hasAnyEditorRows = $hasEshUi || $hasTypography || $colorTokens !== [] || $hasGradients || $hasOther;
$eshUiModuleFilters = $hasEshUi
    ? \App\Helpers\EshUiTokenCatalog::editorModuleFilters($eshUiTokenGroups)
    : [];

$eshRouterScript = basename(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php')));
if ($eshRouterScript === '' || !preg_match('/\.php$/i', $eshRouterScript)) {
    $eshRouterScript = 'index.php';
}
$eshThemeApiQuery = static function (string $action, array $params = []): string {
    return esh_url('Theme', $action, $params);
};

$eshThemeHintBadge = static function (string $hint): string {
    $label = \App\Helpers\ThemeCssColorHelper::tokenHintLabel($hint);
    return '<span class="badge bg-light text-secondary border fw-normal">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
};
?>
<div class="esh-page esh-page--list esh-page-theme container-fluid py-4" id="esh-theme-editor"
     data-theme="<?= htmlspecialchars($editorThemeSlug, ENT_QUOTES, 'UTF-8') ?>"
     data-standalone="<?= $editorStandalone ? '1' : '0' ?>"
     data-esh-ui-preview-anchors="<?= htmlspecialchars(json_encode(\App\Helpers\EshUiTokenCatalog::modulePreviewAnchors(), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
     data-save-url="<?= htmlspecialchars($eshThemeApiQuery('saveColors'), ENT_QUOTES, 'UTF-8') ?>"
     data-preview-save-url="<?= htmlspecialchars($eshThemeApiQuery('previewSave'), ENT_QUOTES, 'UTF-8') ?>"
     data-session-url="<?= htmlspecialchars($eshThemeApiQuery('applySessionPreview'), ENT_QUOTES, 'UTF-8') ?>"
     data-clear-session-url="<?= htmlspecialchars($eshThemeApiQuery('clearSessionPreview'), ENT_QUOTES, 'UTF-8') ?>"
     data-main-app-url="<?= htmlspecialchars(\App\Helpers\ThemeViewHelper::editorPageUrl($editorThemeSlug, true), ENT_QUOTES, 'UTF-8') ?>"
     data-has-rows="<?= $hasAnyEditorRows ? '1' : '0' ?>">
    <?php if ($editorStandalone): ?>
    <header class="esh-theme-editor-standalone-bar mb-3 py-2 px-3 rounded border bg-white shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h1 class="h6 mb-0 fw-bold"><i class="fa-solid fa-sliders me-1 text-primary"></i>Tema editörü</h1>
            <span class="badge bg-primary-subtle text-primary border"><?= htmlspecialchars(\App\Helpers\ThemeViewHelper::themeDisplayName($editorThemeSlug), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 small">
            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(\App\Helpers\ThemeViewHelper::editorPageUrl($editorThemeSlug, true), ENT_QUOTES, 'UTF-8') ?>">Admin kabuğunda aç</a>
            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(esh_url('Theme', 'index'), ENT_QUOTES, 'UTF-8') ?>">Tema listesi</a>
        </div>
    </header>
    <?php else: ?>
    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars(esh_url('Dashboard', 'admin'), ENT_QUOTES, 'UTF-8') ?>">Yönetim paneli</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars(esh_url('Theme', 'index'), ENT_QUOTES, 'UTF-8') ?>">Tema görünümü</a></li>
            <li class="breadcrumb-item active">Tema editörü</li>
        </ol>
    </nav>
    <?php endif; ?>


<?php include __DIR__ . '/partials/session_preview_bar.php'; ?>

    <div class="row g-3 align-items-start">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 esh-theme-editor-card">
                <div class="card-header bg-white py-3 d-flex flex-wrap gap-2 justify-content-between align-items-center esh-theme-editor-card__head">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-sliders me-2 text-primary"></i>Tema editörü</h5>
                    <form method="get" class="d-flex flex-wrap gap-2 align-items-center mb-0">
                        <input type="hidden" name="controller" value="Theme">
                        <input type="hidden" name="action" value="editor">
                        <?php if (!$editorStandalone): ?>
                        <input type="hidden" name="embed" value="1">
                        <?php endif; ?>
                        <label class="small text-muted mb-0" for="esh-theme-select">Tema</label>
                        <select id="esh-theme-select" name="theme" class="form-select form-select-sm" style="min-width: 12rem;" data-esh-auto-submit>
                            <?php foreach ($themesMeta as $row): ?>
                                <?php $slug = (string) ($row['slug'] ?? ''); ?>
                                <option value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"<?= $slug === $editorThemeSlug ? ' selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($row['name'] ?? $slug), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <?php if (!$hasAnyEditorRows): ?>
                <div class="card-body">
                    <p class="text-muted small mb-0">
                        <code>templates/<?= htmlspecialchars($editorThemeSlug, ENT_QUOTES, 'UTF-8') ?>/theme.css</code>
                        içinde düzenlenebilir renk, tipografi, gradyan veya diğer stil girdisi bulunamadı.
                    </p>
                </div>
                <?php else: ?>
<?php include __DIR__ . '/partials/editor_tabs_nav.php'; ?>
                <div class="card-body tab-content esh-theme-editor-tab-body">
<?php include __DIR__ . '/partials/pane_esh_ui.php'; ?>
<?php include __DIR__ . '/partials/pane_colors.php'; ?>
<?php include __DIR__ . '/partials/pane_typography.php'; ?>
<?php include __DIR__ . '/partials/pane_gradients.php'; ?>
<?php include __DIR__ . '/partials/pane_other.php'; ?>
                </div>
                <div class="card-footer bg-white d-flex flex-wrap gap-2 justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="esh-theme-editor-reset">
                        <i class="fa-solid fa-rotate-left me-1"></i>Sıfırla
                    </button>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars(esh_url('Theme', 'index'), ENT_QUOTES, 'UTF-8') ?>">Listeye dön</a>
                        <button type="button" class="btn btn-primary btn-sm" id="esh-theme-editor-save">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Dosyaya kaydet
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-6 esh-theme-preview-col">
            <div class="card shadow-sm border-0 esh-theme-preview-panel">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-eye me-2 text-secondary"></i>Canlı önizleme (iframe)</h6>
                </div>
                <div class="card-body p-0 esh-theme-preview-frame-wrap">
                    <iframe id="esh-theme-preview-frame"
                            title="Tema önizlemesi"
                            src="<?= htmlspecialchars($eshThemeApiQuery('previewShell', ['theme' => $editorThemeSlug]), ENT_QUOTES, 'UTF-8') ?>"
                            class="w-100 border-0"
                            style="background: #f8f9fa;"></iframe>
                </div>
                <div class="card-footer bg-white small text-muted">
                    Iframe: üst menü, datepicker, dashboard takvim, mega menü, giriş kartı, Tom Select, rota/hasta sekmeleri, tablo, uyarılar. Oturum önizlemesi tüm sayfalara <code>theme-sheet.php</code> ile uygulanır.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="esh-theme-save-diff-modal" tabindex="-1" aria-labelledby="esh-theme-save-diff-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="esh-theme-save-diff-title">Kayıt onayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-2" id="esh-theme-save-diff-summary"></p>
                <p class="small text-muted mb-2">
                    <i class="fa-solid fa-shield-halved me-1"></i>
                    Kayıt öncesi <code>theme.css.bak-YYYYMMDD-HHMMSS</code> yedeği alınır; son <strong>20</strong> yedek tutulur.
                </p>
                <pre class="esh-theme-save-diff-list small font-monospace bg-light border rounded p-2 mb-0" id="esh-theme-save-diff-content"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary btn-sm" id="esh-theme-save-diff-confirm">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Onayla ve kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script<?= esh_csp_nonce_attr() ?> type="application/json" id="esh-theme-editor-initial">
<?= json_encode([
    'tokens' => $colorTokens,
    'gradient_vars' => $gradientVarTokens,
    'gradient_props' => $gradientPropEntries,
    'other_vars' => $otherVarTokens,
    'other_props' => $otherPropEntries,
    'esh_ui_vars' => array_merge($eshUiTokens, $eshUiTypographyTokens),
    'typography_vars' => $typographyVarTokens,
    'typography_props' => $typographyPropEntries,
    'sessionPreview' => $sessionPreview,
    'sessionPreviewActive' => $sessionPreviewActive,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>
</script>
