<?php
use App\Helpers\DateHelper;
use App\Helpers\PageShellHelper;

$etkenCount = (int) ($etkenCount ?? 0);
$ilacCount = (int) ($ilacCount ?? 0);
$etkenWithoutIlacCount = (int) ($etkenWithoutIlacCount ?? 0);
$statsAjaxUrl = (string) ($statsAjaxUrl ?? '');
$scrapeWorkerRunning = !empty($scrapeWorkerRunning);
$inProgressImportLog = null;
$importLog = $importLog ?? null;
if ($scrapeWorkerRunning && $importLog !== null && empty($importLog->finished_at)) {
    $inProgressImportLog = $importLog;
}
$scrapedHint = '';
if ($importLog && !empty($importLog->finished_at)) {
    $scrapedHint = 'Son veri aktarımı: ' . DateHelper::toTrDotDateTimeOrEmpty($importLog->finished_at);
} elseif ($scrapeWorkerRunning && $importLog && empty($importLog->finished_at)) {
    $scrapedHint = 'Veri aktarımı devam ediyor (başlangıç: '
        . DateHelper::toTrDotDateTimeOrEmpty($importLog->started_at ?? '') . ').';
} elseif ($importLog && empty($importLog->finished_at)) {
    $scrapedHint = 'Son aktarım yarım kaldı (başlangıç: '
        . DateHelper::toTrDotDateTimeOrEmpty($importLog->started_at ?? '')
        . '). Yönetimden devam ettirilebilir.';
}
?>
<?php
PageShellHelper::pageOpen([
    'kind' => 'list',
    'module' => 'ilac_rehber',
    'id' => 'esh-ilac-rehber-index',
    'attrs' => [
        'data-etken-ajax-url' => (string) ($etkenAjaxUrl ?? ''),
    ],
]);
PageShellHelper::pageHeader(
    'İlaç rehberi (etken madde)',
    'Bilgilendirme amaçlı snapshot; tıbbi karar veya reçete yerine geçmez. Resmi kaynak: TİTCK / hekim reçetesi.',
    '<a href="' . htmlspecialchars(esh_url('IlacRehber', 'search'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-outline-secondary btn-sm me-1">Kullanıcı araması</a>'
        . '<a href="' . htmlspecialchars(esh_url('IlacRehber', 'about'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-outline-secondary btn-sm">Veri kaynağı</a>',
    ['icon' => 'fa-solid fa-book-medical']
);
?>

<div class="alert alert-warning border-0 shadow-sm mb-4" role="status">
    <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
    Bu liste panel dışından tek seferlik çekilmiş özet veridir; güncel fiyat, kısıtlama ve klinik karar için resmi kaynaklara başvurun.
    <?php if ($scrapedHint !== ''): ?>
    <span class="d-block small mt-1 text-muted"><?= htmlspecialchars($scrapedHint, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/_live_stats_panel.php'; ?>

<section class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <label for="esh-rehber-etken-q" class="form-label fw-semibold">Etken madde ara</label>
        <input type="search" id="esh-rehber-etken-q" class="form-control form-control-lg"
               placeholder="En az 1 karakter (ör. parasetamol, amoksisilin)"
               autocomplete="off" spellcheck="false">
        <p class="small text-muted mb-0 mt-2">
            Kayıtlı etken sayısı yukarıdaki canlı özetten güncellenir.
        </p>
    </div>
</section>

<section class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h2 class="h6 mb-0">Sonuçlar</h2>
    </div>
    <div class="list-group list-group-flush" id="esh-rehber-etken-results">
        <div class="list-group-item text-muted small py-4 text-center" id="esh-rehber-etken-placeholder">
            Aramak için yukarıya etken madde adı yazın.
        </div>
    </div>
</section>

<?php PageShellHelper::pageClose(); ?>
