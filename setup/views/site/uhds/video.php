<?php
declare(strict_types=1);
/**
 * UHDS personel görüntülü görüşme odası.
 *
 * @var string $uhdsAppointmentId
 * @var string $uhdsRoomId
 * @var string $uhdsStaffDisplayName
 * @var string $uhdsHastaLabel
 * @var string $uhdsPatientJoinUrl
 * @var string $uhdsInviteMessage
 * @var string $uhdsWhatsappShareUrl
 * @var string $uhdsNativeSmsShareUrl
 * @var string $uhdsSmsComposeUrl
 * @var string $uhdsVisitCreateUrl
 * @var bool $uhdsAutoPromptVisit
 * @var string $uhdsJitsiScriptUrl
 * @var string $uhdsVideoConfigUrl
 * @var string $uhdsCompleteUrl
 */
?>
<div class="container-fluid px-3 px-lg-4 py-4 esh-page-uhds-video">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0"><i class="fa-solid fa-video text-primary me-2"></i>UHDS görüntülü görüşme</h1>
            <p class="text-muted small mb-0"><?= htmlspecialchars($uhdsHastaLabel ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a href="<?= htmlspecialchars(esh_url('Uhds', 'index', ['date' => date('Y-m-d')]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i>Takvime dön
        </a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2">
                    <div id="esh-uhds-jitsi" class="esh-uhds-jitsi-frame bg-dark rounded-3" style="min-height: 420px;"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <h2 class="h6 fw-bold mb-0">Hasta daveti</h2>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Bu bağlantıyı hastaya SMS veya mesajla gönderin. Giriş gerektirmez.</p>
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" class="form-control font-monospace" id="esh-uhds-patient-link" readonly value="<?= htmlspecialchars($uhdsPatientJoinUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="btn btn-outline-primary" id="esh-uhds-copy-link" title="Kopyala">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($uhdsWhatsappShareUrl)): ?>
                        <a href="<?= htmlspecialchars($uhdsWhatsappShareUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm rounded-pill">
                            <i class="fa-brands fa-whatsapp me-1" aria-hidden="true"></i>WhatsApp ile gönder
                        </a>
                        <?php else: ?>
                        <span class="btn btn-success btn-sm rounded-pill disabled opacity-50" title="Hastanın cep telefonu kayıtlı değil" aria-disabled="true">
                            <i class="fa-brands fa-whatsapp me-1" aria-hidden="true"></i>WhatsApp ile gönder
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($uhdsSmsComposeUrl)): ?>
                        <a href="<?= htmlspecialchars($uhdsSmsComposeUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                            <i class="fa-solid fa-comment-sms me-1" aria-hidden="true"></i>SMS gönder
                        </a>
                        <?php elseif (!empty($uhdsNativeSmsShareUrl)): ?>
                        <a href="<?= htmlspecialchars($uhdsNativeSmsShareUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                            <i class="fa-solid fa-comment-sms me-1" aria-hidden="true"></i>SMS ile gönder
                        </a>
                        <?php else: ?>
                        <span class="btn btn-outline-primary btn-sm rounded-pill disabled opacity-50" title="SMS gönderimi için cep telefonu veya SMS modülü gerekli" aria-disabled="true">
                            <i class="fa-solid fa-comment-sms me-1" aria-hidden="true"></i>SMS ile gönder
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h2 class="h6 fw-bold mb-0">Görüşme sonu</h2>
                </div>
                <div class="card-body">
                    <form id="esh-uhds-complete-form" method="post" action="<?= htmlspecialchars($uhdsCompleteUrl ?? '', ENT_QUOTES, 'UTF-8') ?>"
                          data-index-url="<?= htmlspecialchars(esh_url('Uhds', 'index'), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($uhdsAppointmentId ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="hasta_geldi" value="1">
                        <?= \App\Helpers\FormHelper::fieldTextarea('telehealth_summary', 'Görüşme notu', '', [
                            'col' => '',
                            'rows' => 4,
                            'class' => 'shadow-sm',
                            'placeholder' => 'Görüşme özeti, öneriler, takip planı…',
                        ]) ?>
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-success rounded-pill" id="esh-uhds-complete-btn">
                                <i class="fa-solid fa-check me-1"></i>Görüşmeyi bitir
                            </button>
                            <?php if (!empty($uhdsAutoPromptVisit) && !empty($uhdsVisitCreateUrl)): ?>
                                <a href="<?= htmlspecialchars($uhdsVisitCreateUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                    <i class="fa-solid fa-notes-medical me-1"></i>İzlem kaydı oluştur
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script<?= esh_csp_nonce_attr() ?>>
window.__eshUhdsVideo = {
    configUrl: <?= json_encode($uhdsVideoConfigUrl ?? '', JSON_UNESCAPED_UNICODE) ?>,
    jitsiScriptUrl: <?= json_encode($uhdsJitsiScriptUrl ?? '', JSON_UNESCAPED_UNICODE) ?>,
    completeUrl: <?= json_encode($uhdsCompleteUrl ?? '', JSON_UNESCAPED_UNICODE) ?>,
    appointmentId: <?= json_encode((string) ($uhdsAppointmentId ?? ''), JSON_UNESCAPED_UNICODE) ?>,
    autoPromptVisit: <?= !empty($uhdsAutoPromptVisit) ? 'true' : 'false' ?>
};
</script>
