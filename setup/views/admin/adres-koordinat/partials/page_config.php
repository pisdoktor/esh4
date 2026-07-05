<script<?= esh_csp_nonce_attr() ?>>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.coordsScan = {
    processNextUrl: <?= json_encode(esh_url('AdresKoordinat', 'processNext'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    missingStart: <?= json_encode((int) ($missingCount ?? 0), JSON_UNESCAPED_UNICODE) ?>,
    totalKapino: <?= json_encode((int) ($totalKapino ?? 0), JSON_UNESCAPED_UNICODE) ?>,
    quotaLimit: <?= json_encode((int) ($quota['limit'] ?? 2500), JSON_UNESCAPED_UNICODE) ?>,
    providerLabel: <?= json_encode((string) ($quota['provider_label'] ?? 'Harita'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    confirm: <?= json_encode(
        'Koordinatsiz kapilar tek tek '
        . (string) ($quota['provider_label'] ?? 'Harita')
        . ' ile taranacak. Bugun en fazla '
        . (int) ($quota['limit'] ?? 2500)
        . ' sorgu yapilir. Devam edilsin mi?',
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>
};
</script>