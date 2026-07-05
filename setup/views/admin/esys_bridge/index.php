<?php
declare(strict_types=1);

use App\Helpers\EsysBridgeHelper;
use App\Helpers\OperationalSettings;

/** @var list<object> $syncLogs */
$syncLogs = $syncLogs ?? [];
$apiConfigured = (bool) ($apiConfigured ?? false);
$apiMode = (string) ($apiMode ?? OperationalSettings::esysBridgeApiMode());
?>
<div class="esh-page esh-page--list esh-page-esys-bridge container-fluid py-4">
    <header class="esh-page__header mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="esh-page__heading h4 mb-1">
                <i class="fa-solid fa-bridge me-2" aria-hidden="true"></i>
                ESYS / AHBS entegrasyon köprüsü
            </h1>
            <p class="esh-page__lead small text-muted mb-0">
                Dosya tabanlı JSON paketleri ile hasta kimlik ve izlem referanslarını çift yönlü eşleyin.
                Resmi HSYS API yayınlandığında API modu devreye alınabilir.
            </p>
        </div>
        <a href="<?= htmlspecialchars(esh_url('EsysCompliance', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-table-list me-1"></i>Alan eşlemesi
        </a>
    </header>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold"><i class="fa-solid fa-file-export text-primary me-2"></i>ESH → ESYS dışa aktar</h2>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Aktif hastalar (başvuru bilgisi dahil), son <?= (int) OperationalSettings::esysBridgeExportVisitDays() ?> günün izlemleri,
                        bekleyen planlar ve e-rapor kayıtları
                        <code>config/esys-field-mapping.json</code> alan adlarıyla JSON paketine dönüştürülür.
                    </p>
                    <ul class="small mb-3">
                        <li>Hasta üst sınırı: <?= (int) OperationalSettings::esysBridgeExportPatientLimit() ?></li>
                        <li>Yalnızca eksik ref (hasta): <?= OperationalSettings::esysBridgeExportOnlyMissingRefs() ? 'Evet' : 'Hayır' ?></li>
                        <li>Yalnızca eksik izlem ref: <?= OperationalSettings::esysBridgeExportOnlyMissingVisitRefs() ? 'Evet' : 'Hayır' ?></li>
                        <li>e-Rapor üst sınırı: <?= (int) OperationalSettings::esysBridgeExportEraporLimit() ?></li>
                    </ul>
                    <a href="<?= htmlspecialchars(esh_url('EsysBridge', 'export'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary rounded-pill">
                        <i class="fa-solid fa-download me-1"></i>JSON indir
                    </a>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold"><i class="fa-solid fa-file-import text-success me-2"></i>ESYS / AHBS → ESH içe aktar</h2>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        ESYS veya AHBS tarafından dönen referans numaralarını <code>esys_*_ref</code> sütunlarına yazar.
                        Eşleme: <strong>esh_id</strong> veya <strong>tckimlik</strong> (hasta); izlem/plan için <strong>esh_id</strong>.
                    </p>
                    <form method="post" action="<?= htmlspecialchars(esh_url('EsysBridge', 'importRefs'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
                        <?= esh_csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="esys-bundle-file">JSON paket dosyası</label>
                            <input type="file" class="form-control form-control-sm" name="bundle_file" id="esys-bundle-file" accept=".json,application/json" required>
                        </div>
                        <button type="submit" class="btn btn-success rounded-pill">
                            <i class="fa-solid fa-upload me-1"></i>Referansları içe aktar
                        </button>
                    </form>
                    <details class="mt-3 small">
                        <summary class="text-primary" style="cursor:pointer;">Örnek içe aktarma JSON</summary>
                        <pre class="bg-body-tertiary p-2 rounded mt-2 mb-0" style="font-size:0.72rem;">{
  "bundle_version": 1,
  "direction": "esys_to_esh",
  "patients": [
    {"esh_id": 12, "tckimlik": "12345678901", "esys_hasta_ref": "ESYS-H-99", "esys_basvuru_ref": "ESYS-B-44"}
  ],
  "visits": [
    {"esh_id": 501, "esys_izlem_ref": "ESYS-I-1001"}
  ],
  "plans": [
    {"esh_id": 88, "esys_plan_ref": "ESYS-P-7"}
  ],
  "eraporlar": [
    {"esh_id": 15, "tckimlik": "12345678901", "esys_erapor_ref": "ESYS-ER-3"}
  ]
}</pre>
                    </details>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h6 mb-0 fw-bold"><i class="fa-solid fa-plug text-secondary me-2"></i>API köprüsü (hazırlık)</h2>
            <span class="badge rounded-pill <?= $apiConfigured ? 'text-bg-success' : 'text-bg-secondary' ?>">
                <?= $apiConfigured ? 'Yapılandırıldı' : 'Kapalı / URL yok' ?>
            </span>
        </div>
        <div class="card-body small text-muted">
            <p class="mb-2">
                Mod: <strong><?= htmlspecialchars(OperationalSettings::esysBridgeMode(), ENT_QUOTES, 'UTF-8') ?></strong>
                · API modu: <strong><?= htmlspecialchars($apiMode, ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if (OperationalSettings::esysBridgeApiBaseUrl() !== ''): ?>
                    · URL: <code><?= htmlspecialchars(OperationalSettings::esysBridgeApiBaseUrl(), ENT_QUOTES, 'UTF-8') ?></code>
                <?php endif; ?>
            </p>
            <form method="post" action="<?= htmlspecialchars(esh_url('EsysBridge', 'retryQueue'), ENT_QUOTES, 'UTF-8') ?>">
                <?= esh_csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill">
                    <i class="fa-solid fa-rotate me-1"></i>Push kuyruğunu tekrar dene
                </button>
            </form>
            <p class="mb-0">
                Resmi ESYS/HSYS HTTP uçları yayınlandığında Ayarlar → Güvenlik → ESYS köprüsü bölümünden API’yi etkinleştirin.
                Şu an yalnızca dosya senkronu desteklenir.
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h2 class="h6 mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i>Son senkron işlemleri</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Yön</th>
                        <th>Durum</th>
                        <th>Dosya</th>
                        <th>Özet</th>
                    </tr>
                </thead>
                <tbody id="esh-esys-sync-log-tbody">
                    <?php include __DIR__ . '/partials/sync_log_rows.php'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
