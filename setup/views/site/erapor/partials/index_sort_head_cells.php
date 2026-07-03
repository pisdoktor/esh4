<?php
/**
 * @var string $ordering
 * @var array|null $eshEraporSortCfg
 */
$eshEraporSortCfg = $eshEraporSortCfg ?? [
    'mode' => 'merge',
    'mergeBase' => ['controller' => 'Erapor', 'action' => 'index'],
];
?>
<?= \App\Helpers\UIHelper::renderSortTh('TC kimlik no', 'hastatckimlik', $ordering, $eshEraporSortCfg) ?>
<?= \App\Helpers\UIHelper::renderSortTh('Hasta ad soyad', 'adsoyad', $ordering, $eshEraporSortCfg) ?>
<?= \App\Helpers\UIHelper::renderSortTh('Rapor tarihi', 'basvurutarihi', $ordering, $eshEraporSortCfg) ?>
<?= \App\Helpers\UIHelper::renderSortTh('Branş', 'brans', $ordering, $eshEraporSortCfg) ?>
<?= \App\Helpers\UIHelper::renderSortTh('Durum', 'kayitlimi', $ordering, $eshEraporSortCfg) ?>
<?= \App\Helpers\UIHelper::renderSortTh('Yenilendi', 'yenilendimi', $ordering, $eshEraporSortCfg) ?>
<?= \App\Helpers\UIHelper::renderSortTh('Neden', 'neden', $ordering, $eshEraporSortCfg) ?>
