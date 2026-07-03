<?php
declare(strict_types=1);

use App\Helpers\OperationalSettings;

$fieldVisitPatient = isset($patient) && is_object($patient) ? $patient : null;
$fieldVisitClient = OperationalSettings::fieldVisitClientConfig($fieldVisitPatient);
?>
<script>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.fieldVisit = <?= json_encode($fieldVisitClient, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
