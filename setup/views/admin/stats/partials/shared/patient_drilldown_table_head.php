<?php
/**
 * Stats hasta drill-down listeleri için sıralanabilir thead satırı.
 *
 * @var string $ordering
 * @var array $eshSortCfg
 * @var string $eshPatientDrilldownDateColumn randevu|mama_bitis|bez_bitis|sonda_degisim|none
 * @var string|null $eshPatientDrilldownDateLabel
 * @var bool $eshPatientDrilldownShowStatus
 * @var bool $eshPatientDrilldownShowIletisim
 */
$eshSortCfg = $eshSortCfg ?? ['mode' => 'orderby', 'pagelink' => ''];
$eshPatientDrilldownDateColumn = $eshPatientDrilldownDateColumn ?? 'randevu';
$eshPatientDrilldownDateLabel = $eshPatientDrilldownDateLabel ?? null;
$eshPatientDrilldownShowStatus = $eshPatientDrilldownShowStatus ?? true;
$eshPatientDrilldownShowIletisim = $eshPatientDrilldownShowIletisim ?? true;
?>
<tr class="align-middle">
    <th width="72" class="text-center text-primary ps-2">
        <i class="fa-solid fa-chart-line"></i><br><small class="x-small">İzlem</small>
    </th>
    <?php if ($eshPatientDrilldownShowStatus): ?>
    <th width="88" class="text-center small">Durum</th>
    <?php endif; ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Hasta Adı', 'h.isim', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $ordering, $eshSortCfg) ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Mahalle / İlçe', 'h.mahalle', $ordering, $eshSortCfg) ?>
    <th class="text-muted">Anne/Baba</th>
    <?= \App\Helpers\UIHelper::renderSortTh('D.Tarihi', 'h.dogumtarihi', $ordering, $eshSortCfg) ?>
    <?php if ($eshPatientDrilldownShowIletisim): ?>
    <th>İletişim</th>
    <?php endif; ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Kayıt', 'h.kayittarihi', $ordering, $eshSortCfg) ?>
    <?php if ($eshPatientDrilldownDateColumn === 'randevu'): ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Randevu', 'h.randevutarihi', $ordering, array_merge($eshSortCfg, ['thClass' => 'text-nowrap'])) ?>
    <?php elseif ($eshPatientDrilldownDateColumn === 'mama_bitis'): ?>
    <?= \App\Helpers\UIHelper::renderSortTh(
        (string) ($eshPatientDrilldownDateLabel ?? 'Mama rapor bitiş'),
        'h.mamaraporbitis',
        $ordering,
        array_merge($eshSortCfg, ['thClass' => 'text-nowrap'])
    ) ?>
    <?php elseif ($eshPatientDrilldownDateColumn === 'bez_bitis'): ?>
    <?= \App\Helpers\UIHelper::renderSortTh(
        (string) ($eshPatientDrilldownDateLabel ?? 'Bez rapor bitiş'),
        'h.bezraporbitis',
        $ordering,
        array_merge($eshSortCfg, ['thClass' => 'text-nowrap'])
    ) ?>
    <?php elseif ($eshPatientDrilldownDateColumn === 'sonda_degisim'): ?>
    <?= \App\Helpers\UIHelper::renderSortTh(
        (string) ($eshPatientDrilldownDateLabel ?? 'Sonda değişim tarihi'),
        'sonda_degisim',
        $ordering,
        array_merge($eshSortCfg, ['thClass' => 'text-nowrap pe-3'])
    ) ?>
    <?php endif; ?>
    <?php if ($eshPatientDrilldownDateColumn !== 'sonda_degisim'): ?>
    <?= \App\Helpers\UIHelper::renderSortTh('Son İzlem', 'sonizlemtarihi', $ordering, array_merge($eshSortCfg, ['thClass' => 'pe-3 text-end'])) ?>
    <?php endif; ?>
</tr>
