<?php
/**
 * @var string $ordering
 * @var string $pagelink
 * @var bool $unifiedPasifTarihiInsteadOfRandevu
 * @var string $unifiedPasifTarihiColumnTitle
 */
$eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];
?>
<tr class="align-middle">
    <th width="72" class="text-center text-primary ps-2">
        <i class="fa-solid fa-chart-line"></i><br><small class="x-small">İzlem</small>
    </th>
    <th width="88" class="text-center small">Durum</th>
    <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı', 'h.isim', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Mahalle / ilçe', 'h.mahalle', $ordering, $eshSortCfg) ?>
    <th class="text-muted">Anne/Baba</th>
    <?= \App\Helpers\UIHelper::renderSortTh('D.Tarihi', 'h.dogumtarihi', $ordering, $eshSortCfg) ?>
    <th>İletişim</th>
    <?= \App\Helpers\UIHelper::renderSortTh('Kayıt', 'h.kayittarihi', $ordering, $eshSortCfg) ?>
    <?php if ($unifiedPasifTarihiInsteadOfRandevu): ?>
    <?= \App\Helpers\UIHelper::renderSortTh(
        (string) $unifiedPasifTarihiColumnTitle,
        'h.pasiftarihi',
        $ordering,
        array_merge($eshSortCfg, ['thClass' => 'text-nowrap'])
    ) ?>
    <?php else: ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Randevu', 'h.randevutarihi', $ordering, array_merge($eshSortCfg, ['thClass' => 'text-nowrap'])) ?>
    <?php endif; ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Son izlem', 'sonizlemtarihi', $ordering, array_merge($eshSortCfg, ['thClass' => 'pe-3 text-end'])) ?>
</tr>
