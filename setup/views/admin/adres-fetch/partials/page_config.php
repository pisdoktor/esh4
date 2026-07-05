<script<?= esh_csp_nonce_attr() ?>>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.adresFetch = {
    startUrl: <?= json_encode(esh_url('AdresFetch', 'start'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    resumeUrl: <?= json_encode(esh_url('AdresFetch', 'resume'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    processNextUrl: <?= json_encode(esh_url('AdresFetch', 'processNext'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    cancelUrl: <?= json_encode(esh_url('AdresFetch', 'cancel'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    statusUrl: <?= json_encode(esh_url('AdresFetch', 'status'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    initialJobId: <?= json_encode(
        (!empty($activeJob) && ($activeJob['status'] ?? '') === 'running') ? (string) ($activeJob['id'] ?? '') : '',
        JSON_UNESCAPED_UNICODE
    ) ?>,
    resumableJobId: <?= json_encode(
        (!empty($resumableJob)) ? (string) ($resumableJob['id'] ?? '') : '',
        JSON_UNESCAPED_UNICODE
    ) ?>,
    resumablePhaseLabel: <?= json_encode(
        (!empty($resumableJob)) ? (string) ($resumableJob['phase_label'] ?? '') : '',
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>,
    confirmTruncate: <?= json_encode(
        'esh_adrestablosu TRUNCATE edilecek; tüm ilçe/mahalle/sokak/kapı kayıtları silinir. Devam edilsin mi?',
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>,
    confirmStart: <?= json_encode(
        'Denizli adres senkronu başlatılacak (tüm ilçeler). Bu işlem uzun sürebilir. Devam edilsin mi?',
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>,
    confirmResume: <?= json_encode(
        'Durdurulmuş senkron kaldığı yerden devam edecek. Devam edilsin mi?',
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>,
    confirmFreshStart: <?= json_encode(
        'Yeni bir senkron başlatılacak; önceki ilerleme kaydı geçersiz olur. TRUNCATE seçmediyseniz veritabanındaki kayıtlar korunur. Devam edilsin mi?',
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>
};
</script>