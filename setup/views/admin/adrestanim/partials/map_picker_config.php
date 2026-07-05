<?php
$config = $GLOBALS['eshAdrestanimMapConfig'] ?? null;
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
echo '<script' . esh_csp_nonce_attr() . '>window.ESH_ADRESTANIM_MAP=' . $json . ';</script>' . "\n";
echo '<style>.esh-adres-mini-map{width:100%;height:220px;background:#ecf0f1}</style>' . "\n";
