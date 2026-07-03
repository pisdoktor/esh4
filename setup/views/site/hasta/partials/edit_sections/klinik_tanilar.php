<?php

declare(strict_types=1);

/** @var string $hast */

/** @var bool $kurumHastalikBos */

$kurumHastalikBos = !empty($kurumHastalikBos);

?>

<p class="text-muted small mb-3">

    <i class="fa-solid fa-info-circle me-1"></i> Hastanın takip edilen kronik hastalıklarını ve güncel tanılarını buradan yönetebilirsiniz.

</p>

<?php if ($kurumHastalikBos): ?>

<div class="alert alert-warning py-2 px-3 mb-3 small" role="alert">

    <i class="fa-solid fa-triangle-exclamation me-1"></i>

    Bu kurum için henüz tanı seçimi yapılmamış.

    <?php if (\App\Helpers\AuthHelper::sessionIsAdmin()): ?>

        <a href="<?= htmlspecialchars(esh_url('Hastalik', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="alert-link">Kurum tanı seçimi</a> ekranından ICD tanılarını atayın.

    <?php endif; ?>

</div>

<?php endif; ?>

<div class="p-3 bg-light rounded border mb-3 esh-tomselect-field">

    <div class="hastalik-secim-alani">

        <?= $hast ?>

    </div>

    <div class="invalid-feedback d-block">En az bir tanı seçiniz.</div>

</div>

