<script>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.adresFetchTarama = {
    orphanReportUrl: <?= json_encode(esh_url('AdresFetch', 'ajaxOrphanReport'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    orphanDeleteUrl: <?= json_encode(esh_url('AdresFetch', 'ajaxDeleteOrphans'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    orphanPurgeUrl: <?= json_encode(esh_url('AdresFetch', 'ajaxPurgeOrphans'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
};
</script>
