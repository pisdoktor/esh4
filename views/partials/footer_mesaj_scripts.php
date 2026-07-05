<?php
if (empty($_SESSION['user_id'])
    || !\App\Helpers\AppSettings::isModuleEnabled('mesajlasma')
    || !\App\Services\MesajService::canUseMessaging(\App\Helpers\AuthHelper::sessionUserId() ?? '')) {
    return;
}
if (is_file(ROOT_PATH . '/public/assets/pages/js/mesaj-unread-ui.js')) {
    echo esh_csp_script_src_tag(ASSETS_URL . '/pages/js/mesaj-unread-ui.js');
}
if (is_file(ROOT_PATH . '/public/assets/pages/js/mesaj-poll.js')) {
    echo '<script' . esh_csp_nonce_attr() . '>window.ESH_PAGE=window.ESH_PAGE||{};window.ESH_PAGE.mesajPollUrl=' . json_encode(esh_url('Mesaj', 'poll'), JSON_UNESCAPED_UNICODE) . ';</script>' . "\n";
    echo esh_csp_script_src_tag(ASSETS_URL . '/pages/js/mesaj-poll.js');
}
