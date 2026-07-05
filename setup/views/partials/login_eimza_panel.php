<?php
declare(strict_types=1);
/**
 * E-imza giriş paneli (auth-login-eimza.js ile uyumlu).
 * @var string $eimzaPanelIdPrefix isteğe bağlı DOM öneki (varsayılan: esh)
 */
$__pfx = isset($eimzaPanelIdPrefix) ? preg_replace('/[^a-z0-9_-]/i', '', (string) $eimzaPanelIdPrefix) : 'esh';
if ($__pfx === '') {
    $__pfx = 'esh';
}
$__eimzaCfg = [
    'localBridgeEnabled' => defined('ESH_EIMZA_LOCAL_BRIDGE_ENABLED') && ESH_EIMZA_LOCAL_BRIDGE_ENABLED,
    'localBridgeBaseUrl' => defined('ESH_EIMZA_LOCAL_BRIDGE_BASE_URL') ? (string) ESH_EIMZA_LOCAL_BRIDGE_BASE_URL : '',
];
?>
<div class="esh-login-eimza-panel" data-eimza-panel>
    <p class="small text-muted mb-3">Akıllı kart / token takılı olmalıdır. PIN ile yerel köprü üzerinden imzalama yapılır.</p>
    <div class="mb-2">
        <label class="form-label small fw-bold text-muted mb-1" for="<?= $__pfx ?>-eimza-tc">TC Kimlik No</label>
        <input id="<?= $__pfx ?>-eimza-tc" type="text" class="form-control form-control-sm" placeholder="11 haneli TC" maxlength="11" inputmode="numeric" autocomplete="off">
    </div>
    <div class="mb-2">
        <label class="form-label small fw-bold text-muted mb-1" for="<?= $__pfx ?>-eimza-pin">E-imza PIN</label>
        <input id="<?= $__pfx ?>-eimza-pin" type="password" class="form-control form-control-sm" placeholder="Token PIN" autocomplete="off">
    </div>
    <input type="hidden" id="<?= $__pfx ?>-eimza-challenge-id" value="">
    <textarea class="d-none" id="<?= $__pfx ?>-eimza-challenge" aria-hidden="true"></textarea>
    <textarea class="d-none" id="<?= $__pfx ?>-eimza-signature" aria-hidden="true"></textarea>
    <textarea class="d-none" id="<?= $__pfx ?>-eimza-certificate" aria-hidden="true"></textarea>
    <div id="<?= $__pfx ?>-eimza-login-message" class="small d-none mb-2" role="status"></div>
    <div class="d-flex flex-wrap gap-2 mt-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="<?= $__pfx ?>-eimza-token-check-btn">
            <i class="fa-solid fa-usb-drive me-1"></i>Token kontrol
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" id="<?= $__pfx ?>-eimza-challenge-btn">Challenge</button>
        <button type="button" class="btn btn-primary btn-sm" id="<?= $__pfx ?>-eimza-login-btn">
            E-imza ile giriş yap
        </button>
    </div>
</div>
<script<?= esh_csp_nonce_attr() ?>>
window.ESH_EIMZA = <?= json_encode($__eimzaCfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<?= esh_csp_script_src_tag(SITEURL . '/public/assets/pages/js/auth-login-eimza.js', 'defer') ?>
