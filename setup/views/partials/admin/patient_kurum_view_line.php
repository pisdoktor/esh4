<?php



declare(strict_types=1);



use App\Helpers\AuthHelper;



/** @var object $hasta */

/** @var object|null $hastaKurum */

if (!AuthHelper::sessionIsSuperAdmin() || empty($hastaKurum)) {

    return;

}



$kurumAd = trim((string) ($hastaKurum->ad ?? ''));

$kurumKod = trim((string) ($hastaKurum->kod ?? ''));

?>

<h6 class="text-primary small fw-bold text-uppercase mb-0"><i class="fa-solid fa-building me-2"></i>Kurum Bilgisi</h6>

<div class="row g-2 esh-kimlik-kurum-wrap">

    <div class="col-12">

        <div class="esh-kimlik-adres-box esh-kimlik-kurum-box rounded border border-secondary border-opacity-50 bg-white shadow-sm p-3 w-100">

            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 gap-md-3 small text-center">

                <span class="text-muted">Kurum:</span>

                <span class="fw-semibold text-dark">

                    <i class="fa-solid fa-building me-1 text-secondary" aria-hidden="true"></i>

                    <?= htmlspecialchars($kurumAd !== '' ? $kurumAd : '—', ENT_QUOTES, 'UTF-8') ?>

                    <?php if ($kurumKod !== ''): ?>

                        <span class="text-muted fw-normal">(<?= htmlspecialchars($kurumKod, ENT_QUOTES, 'UTF-8') ?>)</span>

                    <?php endif; ?>

                </span>

                <a href="<?= htmlspecialchars(esh_url('Patient', 'changeKurum', ['id' => (int) ($hasta->id ?? 0)]), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Kurum değiştir</a>

            </div>

        </div>

    </div>

</div>

