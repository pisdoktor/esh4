<script>
window.ESH_HARITA = {
    patients: <?= $mapPatientsJson ?>,
    center: [<?= htmlspecialchars((string) $centerLon, ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars((string) $centerLat, ENT_QUOTES, 'UTF-8') ?>],
    mapKeyUrl: <?= json_encode($mapKeyUrl ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    routeUrl: <?= json_encode($routeUrl ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    mahalleCombinedUrl: <?= json_encode($mahalleCombinedUrl ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    mahalleGeoNameAllowlist: <?= json_encode($mahalleGeoNameAllowlist ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
};