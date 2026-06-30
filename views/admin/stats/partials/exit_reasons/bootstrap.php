<?php
/** @var array $rows */
/** @var string $date_from */
/** @var string $date_to */
/** @var string $date_from_ymd */
/** @var string $date_to_ymd */
$filterExpanded = isset($_GET['date_from']) || isset($_GET['date_to']);
$nedenAyarlar = [
    '1' => ['isim' => 'İyileşme', 'renk' => '#FF6384'],
    '2' => ['isim' => 'Vefat', 'renk' => '#36A2EB'],
    '3' => ['isim' => 'İkamet değişikliği', 'renk' => '#FFCE56'],
    '4' => ['isim' => 'Tedaviyi reddetme', 'renk' => '#4BC0C0'],
    '5' => ['isim' => 'Tedaviye yanıt alamama', 'renk' => '#9966FF'],
    '6' => ['isim' => 'Sonlandırmanın talep edilmesi', 'renk' => '#FF9F40'],
    '7' => ['isim' => 'Tedaviye personel gerekmemesi', 'renk' => '#C9CBCF'],
    '8' => ['isim' => 'ESH takibine uygun olmaması', 'renk' => '#2ECC71'],
];
$labels = [];
$data = [];
$colors = [];
$toplam = 0;
$tableRows = [];
foreach ($rows as $row) {
    $id = (string) $row->pasifnedeni;
    if (isset($nedenAyarlar[$id])) {
        $labels[] = $nedenAyarlar[$id]['isim'];
        $data[] = (int) $row->sayi;
        $colors[] = $nedenAyarlar[$id]['renk'];
        $toplam += (int) $row->sayi;
        $tableRows[] = ['isim' => $nedenAyarlar[$id]['isim'], 'sayi' => (int) $row->sayi, 'renk' => $nedenAyarlar[$id]['renk']];
    }
}
?>