<?php
declare(strict_types=1);
/** Rota haritası bootstrap — harita proxy uç noktaları; anahtar HTML'e gömülmez. */
?>
<script>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.showRoute = {
    allData: <?= json_encode($data['tum_vardiya_verisi'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
    merkez: <?= json_encode($data['merkez'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    mapConfigUrl: <?= json_encode(esh_url('Dashboard', 'mapConfigAjax'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    mapKeyUrl: <?= json_encode(esh_url('Dashboard', 'tomtomMapKeyAjax'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    routeUrl: <?= json_encode(esh_url('Dashboard', 'routeAjax'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    showRouteBaseUrl: <?= json_encode(esh_url('Dashboard', 'showRoute'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    startName: <?= json_encode(defined('START_NAME') ? START_NAME : 'Merkez', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
};
</script>
