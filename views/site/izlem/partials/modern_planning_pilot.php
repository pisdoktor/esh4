<?php
declare(strict_types=1);

use App\Helpers\ModernFrontendHelper;

if (!ModernFrontendHelper::planningPilotActive()) {
    return;
}
$boot = ModernFrontendHelper::clientBootConfig('planning');
?>
<div class="alert alert-light border shadow-sm mb-3 esh-modern-pilot-card esh-modern-pilot-planning" id="esh-modern-pilot-wrap">
    <div id="esh-modern-pilot-root" data-api-url="<?= htmlspecialchars((string) ($boot['apiUrl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
</div>
<script<?= esh_csp_nonce_attr() ?>>
window.ESH_MODERN_PILOT = <?= json_encode([
    'scope' => 'planning',
    'vueUrl' => $boot['vueUrl'] ?? ModernFrontendHelper::DEFAULT_VUE_CDN,
    'useBuiltBundle' => !empty($boot['useBuiltBundle']),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<link rel="stylesheet" href="<?= htmlspecialchars(ModernFrontendHelper::assetUrl('modern-pilot.css'), ENT_QUOTES, 'UTF-8') ?>">
<?= esh_csp_script_src_tag(ModernFrontendHelper::assetUrl('modern-pilot-loader.js'), 'type="module"') ?>
