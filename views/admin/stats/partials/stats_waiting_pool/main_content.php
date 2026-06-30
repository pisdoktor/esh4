<p class="mb-3">Pasif kodu <code>-3</code> (bekleyen) toplam: <strong><?= $total ?></strong></p>
    <div class="row g-3">
        <div class="col-md-4"><?php $renderTable('İlçe (ilk 20)', $report['by_ilce'] ?? [], 'ilce'); ?></div>
        <div class="col-md-4"><?php $renderTable('Bağımlılık', $report['by_bagimlilik'] ?? [], 'bagimlilik'); ?></div>
        <div class="col-md-4"><?php $renderTable('Randevu zamanı', $report['by_zaman'] ?? [], 'randevu_zaman'); ?></div>