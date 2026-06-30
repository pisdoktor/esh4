<?php
/**
 * @var string $date_from
 * @var string $date_to
 * @var string $date_from_ymd
 * @var string $date_to_ymd
 * @var array $report
 */
use App\Helpers\IzlemYapilmamaNedenHelper;
use App\Helpers\ZamanDilimiHelper;

$summary = $report['summary'] ?? (object) [];
$byArac = is_array($report['by_arac'] ?? null) ? $report['by_arac'] : [];
$byMonth = is_array($report['by_month'] ?? null) ? $report['by_month'] : [];
$byZaman = is_array($report['by_zaman'] ?? null) ? $report['by_zaman'] : [];
$byNeden = is_array($report['by_neden'] ?? null) ? $report['by_neden'] : [];
$ok = !empty($report['ok']);

$aracMeta = [
    1 => ['label' => 'Araçlı izlem', 'badge' => 'bg-primary-subtle text-primary'],
    0 => ['label' => 'Araçsız izlem', 'badge' => 'bg-secondary-subtle text-secondary'],
];

$nedenLabels = IzlemYapilmamaNedenHelper::labels();

$toplam = (int) ($summary->toplam ?? 0);
$yapilan = (int) ($summary->yapilan ?? 0);
$yapilmayan = (int) ($summary->yapilmayan ?? 0);
$benzersiz = (int) ($summary->benzersiz_hasta ?? 0);
$oran = $summary->tamamlanma_orani ?? null;

$countWithShare = static function (int $part, int $whole): string {
    $n = number_format($part, 0, ',', '.');
    if ($whole <= 0) {
        return htmlspecialchars($n, ENT_QUOTES, 'UTF-8');
    }
    $pct = (int) round(100.0 * $part / $whole);

    return htmlspecialchars($n, ENT_QUOTES, 'UTF-8')
        . ' <span class="small fw-normal opacity-75">(%' . $pct . ')</span>';
};

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
    $chartDone[] = (int) ($row->yapilan ?? 0);
    $chartPending[] = (int) ($row->yapilmayan ?? 0);
}

$aracMap = [];
foreach ($byArac as $row) {
    $k = (int) ($row->arac_kod ?? 0);
    $aracMap[$k] = $row;
}

$nedenMap = [];
foreach ($byNeden as $row) {
    $k = (int) ($row->neden_kod ?? 8);
    $nedenMap[$k] = (int) ($row->adet ?? 0);
}
?>