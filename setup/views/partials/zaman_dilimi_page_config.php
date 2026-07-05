<?php
if (!class_exists(\App\Helpers\ZamanDilimiHelper::class, false)) {
    return;
}
$__zamanSections = \App\Helpers\ZamanDilimiHelper::uiSections();
echo '<script' . esh_csp_nonce_attr() . '>window.ESH_PAGE=window.ESH_PAGE||{};window.ESH_PAGE.zamanDilimleri='
    . json_encode($__zamanSections, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)
    . ';</script>' . "\n";
unset($__zamanSections);
