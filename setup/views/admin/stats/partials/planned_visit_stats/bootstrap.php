<?php
/**
 * @var string $date_from
 * @var string $date_to
 * @var string $date_from_ymd
 * @var string $date_to_ymd
 * @var array $report
 */
use App\Helpers\ZamanDilimiHelper;

$summary = $report['summary'] ?? (object) [];
$byPriority = is_array($report['by_priority'] ?? null) ? $report['by_priority'] : [];
$byMonth = is_array($report['by_month'] ?? null) ? $report['by_month'] : [];
$byZaman = is_array($report['by_zaman'] ?? null) ? $report['by_zaman'] : [];
$ok = !empty($report['ok']);

$oncelikMeta = [
    1 => ['label' => 'Normal', 'badge' => 'bg-success-subtle text-success'],
    2 => ['label' => 'Orta (Öncelikli)', 'badge' => 'bg-warning-subtle text-warning'],
    3 => ['label' => 'Yüksek (Acil)', 'badge' => 'bg-danger-subtle text-danger'],
];

$toplam = (int) ($summary->toplam ?? 0);
$tamamlanan = (int) ($summary->tamamlanan ?? 0);
$bekleyen = (int) ($summary->bekleyen ?? 0);
$gecikmis = (int) ($summary->gecikmis ?? 0);
$benzersiz = (int) ($summary->benzersiz_hasta ?? 0);
$oran = $summary->tamamlanma_orani ?? null;

$turkceAylar = [
    1 => 'Oca', 2 => 'Şub', 3 => 'Mar', 4 => 'Nis', 5 => 'May', 6 => 'Haz',
    7 => 'Tem', 8 => 'Ağu', 9 => 'Eyl', 10 => 'Eki', 11 => 'Kas', 12 => 'Ara',
];

$chartLabels = [];
$chartDone = [];
$chartPending = [];
foreach ($byMonth as $row) {
    $yil = (int) ($row->yil ?? 0);
    $ay = (int) ($row->ay ?? 0);
    $chartLabels[] = ($turkceAylar[$ay] ?? (string) $ay) . ' ' . $yil;
    $chartDone[] = (int) ($row->tamamlanan ?? 0);
    $chartPending[] = (int) ($row->bekleyen ?? 0);
}

$priorityMap = [];
foreach ($byPriority as $row) {
    $k = (int) ($row->oncelik_kod ?? 1);
    $priorityMap[$k] = $row;
}
?>