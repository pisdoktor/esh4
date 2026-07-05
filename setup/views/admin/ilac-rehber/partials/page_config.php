<script<?= esh_csp_nonce_attr() ?>>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.ilacRehberMigration = {
    csrfToken: <?= json_encode((string) ($csrfToken ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    startUrl: <?= json_encode(esh_url('IlacRehber', 'scrapeStart'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    statusUrl: <?= json_encode(esh_url('IlacRehber', 'scrapeStatus'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    cancelUrl: <?= json_encode(esh_url('IlacRehber', 'scrapeCancel'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    initialActive: <?= json_encode($initialActivePayload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>,
    initialStatus: <?= json_encode($initialStatus, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>
};
</script>