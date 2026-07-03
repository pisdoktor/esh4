<?php
/** @var object $s */
/** @var string|null $selectedDevice */
/** @var string|null $selectedDeviceLabel */
/** @var string $specialDevicesRowsFetchUrl */
use App\Helpers\PatientClinicalFlagsHelper;

$labels = [];
foreach (PatientClinicalFlagsHelper::statsReportLabels() as $k => $t) {
    $labels[] = ['k' => $k, 't' => $t];
}
?>
