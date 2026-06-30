<?php
/*
 * ESH Default tema — view sözleşmesi (yeni tema yazımı için)
 * Yol: templates/default/site/rota/ekip_karti.php
 *
 * Controller : (şu an resolveAreaView ile çağrılmıyor)
 * Action     : —
 * Canonical  : views/site/rota/ekip_karti.php (bu dosya genelde include eder)
 *
 * Değişkenler (include öncesi controller kapsamı):
 *   (legacy partial)
 *
 * Ortak: $_SESSION['user_id'], SITEURL, ROOT_PATH, UPLOADS_URL (tanımlıysa)
 */
$__ekipKartiRenk = htmlspecialchars((string) ($t['color'] ?? '#64748b'), ENT_QUOTES, 'UTF-8');
?>
<div class="card mb-4 shadow-sm" style="border-top: 4px solid <?= $__ekipKartiRenk ?>;">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0 fw-bold" style="color: <?= $__ekipKartiRenk ?>;">
            <i class="fa fa-car me-2"></i><?php echo htmlspecialchars((string) $eData['isim'], ENT_QUOTES, 'UTF-8'); ?>
        </h6>
        </div>
    
<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" style="font-size: 0.85rem;">
            <tbody>
                <?php foreach($eData['hastalar'] as $h): ?>
                    <tr <?php if($h->oncelik == 2) echo 'class="table-danger" style="--bs-table-bg: #fff5f5;"'; ?>>
                        
                        <td style="width: 70px;" class="text-center border-end bg-light-subtle">
                            <div class="fw-bold text-primary"><?php echo htmlspecialchars((string) $h->varis_saati, ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>

                        <td class="px-3" style="min-width: 150px;">
                            <div class="fw-bold text-dark text-nowrap"><?php echo htmlspecialchars(trim((string) $h->isim . ' ' . (string) $h->soyisim), ENT_QUOTES, 'UTF-8'); ?><?php echo \App\Helpers\UIHelper::planOncelikRouteIcon($h->oncelik ?? 1); ?></div>
                            <div class="text-muted small">
                                <?php require __DIR__ . '/partials/mahalle_bolge.php'; ?>
                            </div>
                        </td>

                        <td class="px-2">
                            <div class="d-flex flex-wrap gap-1 justify-content-start">
                                <?php 
                                    // Etiketleri Bootstrap 5 badge formatına çevir
                                    $etiket = str_replace('label label-', 'badge fw-medium bg-', $h->etiket);
                                    
                                    // Eğer etiketler virgülle ayrılmış birden fazla işlem içeriyorsa 
                                    // her birini ayrı birer rozet gibi göstermek için küçük bir dokunuş:
                                    if(strpos($etiket, ',') !== false) {
                                        echo str_replace(', ', '</span><span class="badge fw-medium bg-secondary">', $etiket);
                                    } else {
                                        echo $etiket;
                                    }
                                ?>
                            </div>
                        </td>

                        <td class="text-end px-3" style="width: 90px;">
                            <span class="badge rounded-pill bg-white text-secondary border fw-normal">
                                +<?php echo number_format($h->mesafe_artisi, 1); ?> km
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
    
    <div class="card-footer bg-light py-2" style="font-size: 0.75rem;">
        <div class="d-flex justify-content-between">
            <span>Hasta: <strong><?php echo count($eData['hastalar']); ?></strong></span>
            <span>Yol: <strong><?php echo number_format($eData['toplam_km'], 1); ?> km</strong></span>
        </div>
    </div>
</div>