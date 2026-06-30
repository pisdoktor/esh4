<?php
/**
 * e-Rapor ↔ hasta uyum — özet kartlar (XHR parçası).
 * @var object $snap
 */
$uyumsuz = (int) ($snap->uyumsuz_toplam ?? 0);
?>
<div class="row g-3 mb-0 text-center" id="esh-erapor-hasta-uyum-summary-cards">
    <div class="col-md-6 col-xl-3">
        <div class="border rounded-3 p-3 bg-info bg-opacity-10">
            <div class="small text-muted text-uppercase">e-Rapor havuzu</div>
            <div class="display-6 fw-bold text-info"><?= (int) ($snap->erapor_toplam ?? 0) ?></div>
            <div class="small text-muted mt-1">
                Kayıtlı işaretli: <?= (int) ($snap->erapor_kayitli_isaret ?? 0) ?>
                · Dışarıdan: <?= (int) ($snap->erapor_disaridan_isaret ?? 0) ?>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="border rounded-3 p-3 border-success border-2">
            <div class="small text-muted text-uppercase">Eşleşen (aktif)</div>
            <div class="display-6 fw-bold text-success"><?= (int) ($snap->erapor_hasta_aktif_eslesen ?? 0) ?></div>
            <div class="small text-muted mt-1">Geçerli TC + aktif hasta kartı</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="border rounded-3 p-3 border-primary border-2">
            <div class="small text-muted text-uppercase">Aktif hasta</div>
            <div class="display-6 fw-bold text-primary"><?= (int) ($snap->hastalar_aktif_eraporlu ?? 0) ?></div>
            <div class="small text-muted mt-1">
                Kartta e-Rapor işaretli · Havuzda: <?= (int) ($snap->hastalar_aktif_havuzda ?? 0) ?>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="border rounded-3 p-3 bg-danger bg-opacity-10">
            <div class="small text-muted text-uppercase">Uyumsuzluk toplamı</div>
            <div class="display-6 fw-bold text-danger"><?= $uyumsuz ?></div>
            <div class="small text-muted mt-1">Tüm uyarı metriklerinin toplamı</div>
        </div>
    </div>
</div>
