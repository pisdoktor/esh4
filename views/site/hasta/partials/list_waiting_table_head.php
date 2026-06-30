<?php $eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink]; ?>
<tr>
    <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı', 'h.isim', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('TC kimlik no', 'h.tckimlik', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Mahalle / ilçe', 'h.mahalle', $ordering, $eshSortCfg) ?>
    <th class="text-muted">Anne / baba adı</th>
    <?= \App\Helpers\UIHelper::renderSortTh('Doğum tarihi', 'h.dogumtarihi', $ordering, $eshSortCfg) ?>
    <th>İletişim</th>
    <?= \App\Helpers\UIHelper::renderSortTh('Kayıt tarihi', 'h.kayittarihi', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Randevu tarihi', 'h.randevutarihi', $ordering, array_merge($eshSortCfg, ['thClass' => 'pe-3'])) ?>
</tr>
