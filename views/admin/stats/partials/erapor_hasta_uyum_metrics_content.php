<?php
/**
 * e-Rapor ↔ hasta uyum — metrik tablosu (XHR parçası).
 *
 * @var list<array{key: string, label: string, group: string, listable: bool, kind: string, count: int}> $metricsRows
 * @var string|null $activeMetric
 * @var bool $compact
 */
$metricsRows = $metricsRows ?? [];
$activeMetric = isset($activeMetric) ? (string) $activeMetric : '';
if ($activeMetric === '') {
    $activeMetric = null;
}
$compact = !empty($compact);

require __DIR__ . '/erapor_hasta_uyum_metrics_table.php';

if (!$compact): ?>
<p class="small text-muted mb-0 mt-3">
    <i class="fa-solid fa-circle-info me-1"></i>
    Karşılaştırma TC üzerinden yapılır. Metrikler tabloda <strong>beş grupta</strong> sıralanır: önce havuz/hasta özetleri, sonra uyumsuzluklar (havuz TC–kart, havuz kimlik–işaret, hasta kartı).
    Özet satırlarda <span class="badge text-bg-light border text-secondary">Özet</span>; uyumsuzlukta sayı &gt; 0 ise <strong>Gör</strong> ile <code>eraporHastaUyumList&amp;metric=…</code> detayına gidersiniz.
</p>
<?php endif; ?>
