<h4 class="fw-bold mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span><i class="fa-solid fa-syringe text-warning me-2"></i>Sonda takibi</span>
    <?php if (\App\Services\Sms\SmsService::canUseSms(\App\Helpers\AuthHelper::sessionUserId())): ?>
    <a href="<?= htmlspecialchars(esh_url('Sms', 'compose', ['segment' => 'sonda_yaklasan']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-comment-sms me-1"></i>Yaklaşanlara SMS
    </a>
    <?php endif; ?>
</h4>
    <?php require dirname(__DIR__, 4) . '/partials/admin/stats_page_intro.php'; ?>