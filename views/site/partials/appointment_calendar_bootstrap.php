<?php
declare(strict_types=1);

/**
 * Randevu / UHDS takvim sayfaları için ortak değişkenler.
 * Önce ayarlayın: $eshAppointmentCalController, $eshAppointmentCalAction (isteğe bağlı, öntanım Randevu/index)
 * Girdi: $y, $m, $selectedDate, $prefillTc
 */
$eshAppointmentCalController = $eshAppointmentCalController ?? 'Randevu';
$eshAppointmentCalAction = $eshAppointmentCalAction ?? 'index';

$weekdayLabels = ['Pt', 'Sa', 'Ça', 'Pe', 'Cu', 'Ct', 'Pz'];
$prefillTc = isset($prefillTc) ? (string) $prefillTc : '';
$prefillHastaLabel = isset($prefillHastaLabel) ? (string) $prefillHastaLabel : '';

$first = new DateTimeImmutable(sprintf('%04d-%02d-01', $y, $m));
$dow = (int) $first->format('N');
$gridStart = $first->modify('-' . ($dow - 1) . ' days');

$baseCal = [
    'controller' => $eshAppointmentCalController,
    'action' => $eshAppointmentCalAction,
];

$linkCal = static function (int $cy, int $cm, ?string $cdate = null) use ($baseCal, $prefillTc): string {
    $p = $baseCal + ['y' => $cy, 'm' => $cm];
    if ($cdate !== null && $cdate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $cdate)) {
        $p['date'] = $cdate;
    }
    if ($prefillTc !== '' && preg_match('/^\d{11}$/', $prefillTc)) {
        $p['tc'] = $prefillTc;
    }

    return \App\Helpers\UrlHelper::fromRequestParams($p);
};

$linkCalNoTc = static function (int $cy, int $cm, ?string $cdate = null) use ($baseCal): string {
    $p = $baseCal + ['y' => $cy, 'm' => $cm];
    if ($cdate !== null && $cdate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $cdate)) {
        $p['date'] = $cdate;
    }

    return \App\Helpers\UrlHelper::fromRequestParams($p);
};

$todayYmd = (new DateTimeImmutable('today'))->format('Y-m-d');
