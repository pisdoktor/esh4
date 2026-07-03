<?php $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink]; ?>
<tr class="align-middle">
    <th width="80" class="text-center text-primary ps-3">
        <i class="fa-solid fa-chart-line"></i><br><small class="x-small">İzlem</small>
    </th>
    <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı', 'h.isim', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Mahalle / ilçe', 'h.mahalle', $ordering, $eshSortCfg) ?>
    <th class="text-muted">Anne/Baba</th>
    <?= \App\Helpers\UIHelper::renderSortTh('D.Tarihi', 'h.dogumtarihi', $ordering, $eshSortCfg) ?>
    <th>İletişim</th>
    <?= \App\Helpers\UIHelper::renderSortTh('Kayıt', 'h.kayittarihi', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Son izlem', 'sonizlemtarihi', $ordering, array_merge($eshSortCfg, ['thClass' => 'pe-3 text-end'])) ?>
</tr>
