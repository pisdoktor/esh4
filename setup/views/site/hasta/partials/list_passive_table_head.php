<?php $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink]; ?>
<tr class="align-middle">
    <th width="80" class="text-center text-primary ps-3">
        <i class="fa-solid fa-chart-line"></i><br><small class="x-small">İzlem</small>
    </th>
    <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı', 'h.isim', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('TC kimlik no', 'h.tckimlik', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Mahalle / ilçe', 'h.mahalle', $ordering, $eshSortCfg) ?>
    <th class="text-muted">Anne / baba adı</th>
    <?= \App\Helpers\UIHelper::renderSortTh('Doğum tarihi', 'h.dogumtarihi', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Kayıt tarihi', 'h.kayittarihi', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Son izlem', 'sonizlemtarihi', $ordering, array_merge($eshSortCfg, ['thClass' => 'pe-3'])) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Pasif tarihi', 'h.pasiftarihi', $ordering, array_merge($eshSortCfg, ['thClass' => 'pe-3'])) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Pasif nedeni', 'h.pasifnedeni', $ordering, array_merge($eshSortCfg, ['thClass' => 'pe-3'])) ?>
</tr>
