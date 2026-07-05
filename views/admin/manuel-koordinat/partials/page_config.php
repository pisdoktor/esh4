<?php
/**
 * Manuel koordinat sayfası istemci yapılandırması.
 */
$config = $GLOBALS['eshManuelKoordinatPageConfig'] ?? null;
if (!is_array($config)) {
    return;
}
$json = json_encode(
    $config,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
if ($json === false) {
    $json = '{}';
}
echo '<script' . esh_csp_nonce_attr() . '>window.ESH_MANUEL_KOORDINAT=' . $json . ';</script>' . "\n";
