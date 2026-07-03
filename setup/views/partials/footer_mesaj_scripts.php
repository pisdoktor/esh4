<?php
if (empty($_SESSION['user_id'])
    || !\App\Helpers\AppSettings::isModuleEnabled('mesajlasma')
    || !\App\Services\MesajService::canUseMessaging((int) $_SESSION['user_id'])) {
    return;
}
if (is_file(ROOT_PATH . '/public/assets/pages/js/mesaj-unread-ui.js')) {
    echo '<script src="' . htmlspecialchars(ASSETS_URL . '/pages/js/mesaj-unread-ui.js', ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
}
if (is_file(ROOT_PATH . '/public/assets/pages/js/mesaj-poll.js')) {
    echo '<script>window.ESH_PAGE=window.ESH_PAGE||{};window.ESH_PAGE.mesajPollUrl=' . json_encode(esh_url('Mesaj', 'poll'), JSON_UNESCAPED_UNICODE) . ';</script>' . "\n";
    echo '<script src="' . htmlspecialchars(ASSETS_URL . '/pages/js/mesaj-poll.js', ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
}
