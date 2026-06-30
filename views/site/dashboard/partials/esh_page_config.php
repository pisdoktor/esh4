<script>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.initialDate = <?= json_encode(date('Y-m-d'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ESH_PAGE.tcLookupUrl = <?= json_encode(esh_url('Dashboard', 'tcLookupAjax'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ESH_PAGE.dashboardPansumanIzlemDefaultIslemId = <?= json_encode(\App\Helpers\IslemIdSettings::resolvedInt('dashboard_pansuman_izlem_default_islem_id'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ESH_PAGE.dashboardCalendarMonthUrl = <?= json_encode(esh_url('Dashboard', 'calendarMonth'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
<?php if (\App\Helpers\AuthHelper::sessionIsAdmin()): ?>
window.ESH_PAGE.dashboardPlanMernisScanUrl = <?= json_encode(esh_url('Dashboard', 'dailyPlanMernisScan'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
<?php endif; ?>
window.ESH_PAGE.canMernisScan = <?= json_encode(\App\Helpers\AuthHelper::sessionIsAdmin(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ESH_PAGE.canDrawRoute = <?= json_encode(\App\Helpers\AuthHelper::sessionIsAdmin(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
<?php if (\App\Services\Sms\SmsService::canUseSms((int) ($_SESSION['user_id'] ?? 0))): ?>
window.ESH_PAGE.canUseDailyPlanSms = true;
window.ESH_PAGE.smsSendConfigured = <?= json_encode(\App\Services\Sms\SmsService::isSendConfigured(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ESH_PAGE.dashboardPlanSmsComposeUrl = <?= json_encode(esh_url('Sms', 'compose', ['segment' => 'gunun_plani']), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.ESH_PAGE.smsSettingsUrl = <?= json_encode(esh_url('Settings', 'index', ['tab' => 'sms']), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
<?php endif; ?>
</script>
