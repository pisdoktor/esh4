<?php
declare(strict_types=1);

$portalError = $_SESSION['portal_error'] ?? '';
unset($_SESSION['portal_error']);
?>
<article class="pha-card portal-dashboard">
    <header class="pha-card__hero portal-dashboard__hero">
        <h1 class="pha-card__title h4 mb-1">SMS dogrulama</h1>
        <p class="pha-card__lead mb-0">Telefonunuza gelen 6 haneli kodu girin.</p>
    </header>
    <div class="pha-card__body">
        <?php if ($portalError !== ''): ?>
            <div class="alert alert-danger small"><?= htmlspecialchars((string) $portalError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <p class="small text-muted">Kod gonderilen numara: <strong><?= htmlspecialchars((string) ($maskedPhone ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
        <form method="post" action="<?= htmlspecialchars(esh_url('PatientPortal', 'verifyOtp', [], true), ENT_QUOTES, 'UTF-8') ?>">
            <?= esh_csrf_field() ?>
            <div class="mb-3">
                <label for="otpCode" class="form-label small fw-semibold">Dogrulama kodu</label>
                <input id="otpCode" name="otp_code" class="form-control" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary rounded-pill">Giris yap</button>
        </form>
    </div>
</article>
