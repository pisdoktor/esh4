<?php
/**
 * @var string $ordering
 * @var array $eshSortCfg
 */
$eshSortCfg = $eshSortCfg ?? ['mode' => 'orderby', 'pagelink' => ''];
?>
<tr class="align-middle">
    <th width="72" class="text-center text-primary ps-2">
        <i class="fa-solid fa-chart-line"></i><br><small class="x-small">İzlem</small>
    </th>
    <?= \App\Helpers\UIHelper::renderSortTh('Hasta Adı', 'h.isim', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Mahalle / İlçe', 'h.mahalle', $ordering, $eshSortCfg) ?>
    <th class="text-muted">Anne/Baba</th>
    <?= \App\Helpers\UIHelper::renderSortTh('D.Tarihi', 'h.dogumtarihi', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Kayıt', 'h.kayittarihi', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Son İzlem', 'sonizlemtarihi', $ordering, array_merge($eshSortCfg, ['thClass' => 'pe-3 text-end'])) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Gün', 'hizmet_suresi_gun', $ordering, array_merge($eshSortCfg, ['thClass' => 'text-nowrap'])) ?>
</tr>
