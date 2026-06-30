<?php
declare(strict_types=1);
/**
 * Admin liste şablonu — kapanış.
 *
 * - full (varsayılan): card-body (açıksa) + card + container
 * - body_only: yalnızca card-body kapanır (örn. Planning: footer öncesi)
 * - card_only: card + container (body zaten kapatılmış olmalı)
 */
$part = isset($admin_list_close_part) ? (string) $admin_list_close_part : 'full';
if (!in_array($part, ['full', 'body_only', 'card_only'], true)) {
    $part = 'full';
}

$bodyOpened = !empty($GLOBALS['_admin_list_body_opened']);

if ($part === 'card_only' && $bodyOpened) {
    echo '</div>';
    $GLOBALS['_admin_list_body_opened'] = false;
    $bodyOpened = false;
}

if ($part === 'full' || $part === 'body_only') {
    if ($bodyOpened) {
        echo '</div>';
        $GLOBALS['_admin_list_body_opened'] = false;
    }
}

if ($part === 'full' || $part === 'card_only') {
    echo '</div>';
    echo '</div>';
    unset($GLOBALS['_admin_list_body_opened']);
}
