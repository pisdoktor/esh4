<?php
/**
 * Veri sağlığı dinamik içerik (özet kartlar + metrik listeleri).
 * @var object $health
 */
$adresRows = [
    ['mahalle_ilce_uyumsuz', 'İlçe–Mahalle uyumsuzluğu'],
    ['sokak_mahalle_uyumsuz', 'Mahalle–Sokak uyumsuzluğu'],
    ['kapino_sokak_uyumsuz', 'Sokak–Kapı no uyumsuzluğu'],
    ['ilce_yok', 'İlçe bilgisi eksik'],
    ['mahalle_yok', 'Mahalle bilgisi eksik'],
    ['sokak_yok', 'Sokak bilgisi eksik'],
    ['kapi_yok', 'Kapı no bilgisi eksik'],
];
$hastaRows = [
    ['hatali_tc', 'Geçersiz TC kimlik no'],
    ['dogum_yok', 'Doğum tarihi belirsiz'],
    ['cinsiyet_yok', 'Cinsiyet bilgisi yok'],
    ['hic_izlenmemis', 'Kaydı olup hiç izlenmeyen'],
    ['kilo_yok', 'Kilo bilgisi yok'],
    ['boy_yok', 'Boy bilgisi yok'],
    ['tel_yok', 'Telefon bilgisi yok'],
    ['guvence_yok', 'Güvence bilgisi yok'],
];
$kritik = (int) ($health->toplam_kritik ?? 0);
$dataHealthListUrl = static function (string $metricKey, int $count): ?string {
    if ($count < 1) {
        return null;
    }
    return \App\Helpers\UrlHelper::fromRequestParams([
        'controller' => 'Stats',
        'action' => 'dataHealthPatients',
        'metric' => $metricKey,
    ]);
};
?>
<div class="row g-3 mb-4 text-center" id="esh-data-health-summary">
    <div class="col-md-4">
        <div class="border rounded-3 p-3 bg-danger bg-opacity-10">
            <div class="small text-muted text-uppercase">Kritik özet</div>
            <div class="display-6 fw-bold text-danger"><?= $kritik ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded-3 p-3 border-info border-2">
            <div class="small text-muted text-uppercase">Adres toplamı</div>
            <div class="display-6 fw-bold text-info"><?= (int) ($health->adres_toplam ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded-3 p-3 border-warning border-2">
            <div class="small text-muted text-uppercase">Hasta &amp; izlem</div>
            <div class="display-6 fw-bold text-warning"><?= (int) ($health->hasta_dosya_toplam ?? 0) ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-map-marked-alt me-2"></i>Adres hiyerarşisi</h6>
        <ul class="list-group list-group-flush border rounded-3">
            <?php foreach ($adresRows as $pair): ?>
                <?php
                $k = $pair[0];
                $n = (int) ($health->$k ?? 0);
                $listUrl = $dataHealthListUrl($k, $n);
                ?>
                <li class="list-group-item <?= $n > 0 ? 'list-group-item-warning p-0' : '' ?>">
                    <?php if ($listUrl !== null): ?>
                        <a href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 rounded-0 text-decoration-none text-dark">
                            <span><?= htmlspecialchars($pair[1], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-primary"><?= $n ?></span>
                        </a>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center px-3 py-2">
                            <span><?= htmlspecialchars($pair[1], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-secondary"><?= $n ?></span>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-user-gear me-2"></i>Hasta dosyası ve izlem</h6>
        <ul class="list-group list-group-flush border rounded-3">
            <?php foreach ($hastaRows as $pair): ?>
                <?php
                $k = $pair[0];
                $n = (int) ($health->$k ?? 0);
                $listUrl = $dataHealthListUrl($k, $n);
                ?>
                <li class="list-group-item <?= $n > 0 ? 'list-group-item-warning p-0' : '' ?>">
                    <?php if ($listUrl !== null): ?>
                        <a href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 rounded-0 text-decoration-none text-dark">
                            <span><?= htmlspecialchars($pair[1], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-primary"><?= $n ?></span>
                        </a>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center px-3 py-2">
                            <span><?= htmlspecialchars($pair[1], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-secondary"><?= $n ?></span>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php if ($kritik > 0): ?>
    <div class="card-footer bg-warning bg-opacity-25 border-0 mt-4 rounded-3" id="esh-data-health-footer">
        <i class="fa-solid fa-circle-exclamation text-danger me-1"></i>
        <strong>Kritik uyarı:</strong> İstatistik güvenilirliği için ilçe, mahalle ve TC hatalarını önceliklendirin.
    </div>
<?php endif; ?>
