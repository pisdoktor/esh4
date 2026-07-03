<?php
declare(strict_types=1);

use App\Helpers\OperationalSettings;
use App\Helpers\UsbsBridgeHelper;

/** @var list<object> $syncLogs */
$syncLogs = $syncLogs ?? [];
$apiConfigured = (bool) ($apiConfigured ?? false);
$apiMode = (string) ($apiMode ?? OperationalSettings::usbsBridgeApiMode());
?>
<div class="esh-page esh-page--list esh-page-usbs-bridge container-fluid py-4">
    <header class="esh-page__header mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="esh-page__heading h4 mb-1">
                <i class="fa-solid fa-heart-pulse me-2" aria-hidden="true"></i>
                USBS / e-Nabız entegrasyon köprüsü
            </h1>
            <p class="esh-page__lead small text-muted mb-0">
                Yapılmış izlemlerin USBS bildirim paketlerini JSON olarak dışa aktarın; e-Nabız / USBS referans numaralarını içe aktarın.
            </p>
        </div>
        <a href="<?= htmlspecialchars(esh_url('UsbsCompliance', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-table-list me-1"></i>Alan eşlemesi
        </a>
    </header>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold"><i class="fa-solid fa-file-export text-primary me-2"></i>ESH → USBS dışa aktar</h2>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Aktif hastalar ve son <?= (int) OperationalSettings::usbsBridgeExportVisitDays() ?> günün <strong>yapılmış</strong> izlemleri
                        <code>config/usbs-field-mapping.json</code> alan adlarıyla JSON paketine dönüştürülür.
                    </p>
                    <ul class="small mb-3">
                        <li>Yalnızca bekleyen bildirim: <?= OperationalSettings::usbsBridgeExportOnlyPending() ? 'Evet' : 'Hayır' ?></li>
                        <li>e-Reçete stub: <?= OperationalSettings::usbsBridgeIncludeEreceteStub() ? 'Dahil' : 'Hayır' ?></li>
                    </ul>
                    <a href="<?= htmlspecialchars(esh_url('UsbsBridge', 'export'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary rounded-pill">
                        <i class="fa-solid fa-download me-1"></i>JSON indir
                    </a>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold"><i class="fa-solid fa-file-import text-success me-2"></i>USBS / e-Nabız → ESH içe aktar</h2>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        USBS veya e-Nabız tarafından dönen referans numaralarını hasta ve izlem kayıtlarına yazar.
                        Eşleme: <strong>esh_id</strong> veya <strong>tckimlik</strong> (hasta); izlem için <strong>esh_id</strong>.
                    </p>
                    <form method="post" action="<?= htmlspecialchars(esh_url('UsbsBridge', 'importRefs'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
                        <?= esh_csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="usbs-bundle-file">JSON paket dosyası</label>
                            <input type="file" class="form-control form-control-sm" name="bundle_file" id="usbs-bundle-file" accept=".json,application/json" required>
                        </div>
                        <button type="submit" class="btn btn-success rounded-pill">
                            <i class="fa-solid fa-upload me-1"></i>Referansları içe aktar
                        </button>
                    </form>
                    <details class="mt-3 small">
                        <summary class="text-primary" style="cursor:pointer;">Örnek içe aktarma JSON</summary>
                        <pre class="bg-body-tertiary p-2 rounded mt-2 mb-0" style="font-size:0.72rem;">{
  "bundle_version": 1,
  "direction": "usbs_to_esh",
  "patients": [
    {"esh_id": 12, "tckimlik": "12345678901", "enabiz_hasta_ref": "EN-99", "usbs_hasta_ref": "USBS-H-44"}
  ],
  "visits": [
    {"esh_id": 501, "usbs_bildirim_ref": "USBS-I-1001", "usbs_bildirim_durum": "sent", "erecete_ref": "ER-55"}
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
            <p class="mb-2">API modu: <strong><?= htmlspecialchars($apiMode, ENT_QUOTES, 'UTF-8') ?></strong></p>
            <?php if (OperationalSettings::usbsBridgeApiBaseUrl() !== ''): ?>
                <p class="mb-2">URL: <code><?= htmlspecialchars(OperationalSettings::usbsBridgeApiBaseUrl(), ENT_QUOTES, 'UTF-8') ?></code></p>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars(esh_url('UsbsBridge', 'retryQueue'), ENT_QUOTES, 'UTF-8') ?>" class="mb-2">
                <?= esh_csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill">
                    <i class="fa-solid fa-rotate me-1"></i>Başarısız bildirimleri tekrar dene
                </button>
            </form>
            <p class="mb-0">
                Resmi USBS / e-Nabız HTTP uçları yayınlandığında Ayarlar → Güvenlik → USBS köprüsü bölümünden API’yi etkinleştirin.
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
                <tbody id="esh-usbs-sync-log-tbody">
                    <?php include __DIR__ . '/partials/sync_log_rows.php'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
