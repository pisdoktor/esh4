<?php
/**
 * Birleşik hasta listesi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var array<int, object> $patients
 * @var string $curStatus
 * @var string $pagelink
 * @var bool $unifiedPasifTarihiInsteadOfRandevu
 */
use App\Helpers\AuthHelper;
if (empty($patients)): ?>
    <tr>
        <td colspan="11" class="border-0 p-0">
            <div class="esh-unified-empty d-flex flex-column align-items-center justify-content-center text-center py-5 text-muted w-100">
                <i class="fa-solid fa-users-slash fa-2x mb-3 opacity-25" aria-hidden="true"></i>
                <p class="mb-1 fw-semibold">Bu filtreye uygun hasta bulunamadı.</p>
                <p class="small mb-0">Arama metnini veya durum seçimini değiştirip tekrar deneyin.</p>
            </div>
        </td>
    </tr>
<?php else: ?>
<?php foreach ($patients as $patient):
    $waitingRowClass = $curStatus === 'waiting'
        ? \App\Helpers\DateHelper::waitingKayitRowClass($patient->kayittarihi ?? '')
        : '';
    ?>
    <tr<?= $waitingRowClass !== '' ? ' class="' . htmlspecialchars($waitingRowClass, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
        <td class="text-center p-1">
            <?= \App\Helpers\UIHelper::patientSummaryButtons(
                (string) ($patient->tckimlik ?? ''),
                (int) ($patient->izlemsayisi ?? 0),
                (int) ($patient->yizlemsayisi ?? 0),
                (int) ($patient->totalplanli ?? 0)
            ) ?>
        </td>
        <td class="text-center"><?= \App\Helpers\BadgeHelper::patientStatusBadgeHtml($patient) ?></td>
        <td>
            <div class="dropdown">
                <a class="patient-link dropdown-toggle d-inline-block text-decoration-none fw-bold"
                   href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                   style="color:<?= \App\Helpers\CinsiyetHelper::nameColor($patient->cinsiyet ?? null) ?>; font-size: 0.82rem;">
                    <?= htmlspecialchars($patient->isim . ' ' . $patient->soyisim) ?>
                </a>
                <?php
                $unifiedMenuIsAdmin = AuthHelper::sessionIsAdmin();
                $eshMenuRows = \App\Helpers\BadgeHelper::patientUnifiedMenuEntries($patient, $unifiedMenuIsAdmin);
                include ROOT_PATH . '/views/site/partials/esh_dropdown_menu.php';
                ?>
            </div>
            <div class="mt-1 d-flex gap-1 flex-wrap">
                <?= \App\Helpers\BadgeHelper::patientFeatures($patient) ?>
            </div>
            <?= \App\Helpers\PatientCompletenessHelper::renderListIndicator($patient) ?>
        </td>
        <td>
            <code class="text-dark" style="font-size: 0.72rem;"><?= \App\Helpers\ValidationHelper::formatTc($patient->tckimlik) ?></code>
        </td>
        <td>
            <div class="d-flex flex-column" style="line-height: 1.2;">
                <span class="small fw-semibold text-dark"><?= htmlspecialchars($patient->mahalle_adi ?? '') ?></span>
                <a href="<?= htmlspecialchars(esh_url('Patient', 'unified', ['status' => $curStatus, 'search' => ($patient->ilce_adi ?? '')]), ENT_QUOTES, 'UTF-8') ?>"
                   class="x-small text-decoration-none"><?= htmlspecialchars($patient->ilce_adi ?? '') ?></a>
            </div>
        </td>
        <td class="x-small text-muted">
            <span class="d-block">A: <?= htmlspecialchars($patient->anneAdi ?? '') ?></span>
            <span class="d-block">B: <?= htmlspecialchars($patient->babaAdi ?? '') ?></span>
        </td>
        <td>
            <span class="small text-dark"><?= \App\Helpers\DateHelper::toTr($patient->dogumtarihi) ?></span><br>
            <span class="badge bg-light text-secondary border x-small"><?= \App\Helpers\DateHelper::calculateAge($patient->dogumtarihi) ?> Y</span>
        </td>
        <td class="x-small">
            <?php if (!empty($patient->ceptel1)): ?>
                <a href="tel:<?= htmlspecialchars($patient->ceptel1) ?>" class="text-decoration-none text-dark fw-bold">
                    <i class="fa-solid fa-mobile-screen text-success me-1"></i><?= htmlspecialchars($patient->ceptel1) ?></a>
            <?php endif; ?>
        </td>
        <td class="x-small text-muted">
            <?= \App\Helpers\DateHelper::toTr($patient->kayittarihi) ?>
        </td>
        <td class="x-small">
            <?php if ($unifiedPasifTarihiInsteadOfRandevu): ?>
            <span class="<?= empty($patient->pasiftarihi) ? 'text-danger' : 'text-success' ?>">
                <?= !empty($patient->pasiftarihi) ? \App\Helpers\DateHelper::toTr($patient->pasiftarihi) : '—' ?>
            </span>
            <?php else: ?>
            <span class="<?= empty($patient->randevutarihi) ? 'text-danger' : 'text-success' ?>">
                <?= !empty($patient->randevutarihi) ? \App\Helpers\DateHelper::toTr($patient->randevutarihi) : '—' ?>
            </span>
            <?php endif; ?>
        </td>
        <td class="pe-3 text-end">
            <span class="small fw-bold <?= empty($patient->sonizlemtarihi) ? 'text-danger' : 'text-success' ?>">
                <?= !empty($patient->sonizlemtarihi) ? \App\Helpers\DateHelper::toTr($patient->sonizlemtarihi) : 'İzlem Yok' ?>
            </span>
        </td>
    </tr>
<?php endforeach; ?>
<?php endif; ?>
