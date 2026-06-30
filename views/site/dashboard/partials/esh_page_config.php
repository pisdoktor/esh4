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
</script>
