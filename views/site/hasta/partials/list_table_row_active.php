<?php
/** @var object $patient */
$isMale = (($patient->cinsiyet ?? '') === '1' || ($patient->cinsiyet ?? '') === 'E');
$genderClass = $isMale ? 'esh-patient-gender-male' : 'esh-patient-gender-female';
?>
<tr class="esh-patient-row-hover">
    <td class="text-center p-1">
        <?php include __DIR__ . '/list_izlem_buttons.php'; ?>
    </td>
    <td>
        <div class="dropdown">
            <a class="patient-link dropdown-toggle d-inline-block text-decoration-none esh-patient-name-link <?= $genderClass ?>"
               href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?= htmlspecialchars($patient->isim . ' ' . $patient->soyisim) ?>
            </a>
            <div class="mt-1 d-flex gap-1 flex-wrap">
                <?= \App\Helpers\BadgeHelper::patientFeatures($patient) ?>
            </div>
            <ul class="dropdown-menu shadow-lg border-0 py-2 custom-dropdown" data-bs-boundary="viewport">
                <li><h6 class="dropdown-header text-dark fw-bold opacity-75">HASTA İŞLEMLERİ</h6></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2" href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => $patient->id]), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-circle-info text-primary me-2"></i> Bilgileri Göster
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2" href="<?= htmlspecialchars(esh_url('Patient', 'edit', ['id' => $patient->id]), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-user-pen text-warning me-2"></i> Düzenle
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2 text-success fw-bold" href="<?= htmlspecialchars(esh_url('Visit', 'create', ['tc' => urlencode($patient->tckimlik ?? '')]), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-plus-circle me-2"></i> Yeni izlem gir
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2 text-primary fw-semibold" href="<?= htmlspecialchars(esh_url('PlannedVisit', 'create', ['tc' => urlencode($patient->tckimlik ?? '')]), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-calendar-plus me-2"></i> Yeni izlem planla
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2" href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => urlencode($patient->tckimlik ?? '')]), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-clock-rotate-left text-info me-2"></i> İzlem geçmişi
                    </a>
                </li>
            </ul>
        </div>
    </td>
    <td>
        <code class="esh-tc-code"><?= \App\Helpers\ValidationHelper::formatTc($patient->tckimlik) ?></code>
    </td>
    <td>
        <div class="d-flex flex-column esh-patient-address-col">
            <span class="small fw-semibold text-dark"><?= $patient->mahalle_adi ?></span>
            <span class="x-small text-muted"><?= $patient->ilce_adi ?></span>
        </div>
    </td>
    <td class="x-small esh-contact-muted">
        <span class="d-block">A: <?= htmlspecialchars($patient->anneAdi) ?></span>
        <span class="d-block">B: <?= htmlspecialchars($patient->babaAdi) ?></span>
    </td>
    <td>
        <span class="small text-dark"><?= \App\Helpers\DateHelper::toTr($patient->dogumtarihi) ?></span><br>
        <span class="badge bg-light text-secondary border x-small"><?= \App\Helpers\DateHelper::calculateAge($patient->dogumtarihi) ?> Y</span>
    </td>
    <td class="x-small">
        <?php if (!empty($patient->ceptel1)): ?>
            <a href="tel:<?= $patient->ceptel1 ?>" class="text-decoration-none text-dark fw-bold">
                <i class="fa-solid fa-mobile-screen text-success me-1"></i><?= $patient->ceptel1 ?></a>
        <?php endif; ?>
    </td>
    <td class="x-small esh-contact-muted">
        <?= \App\Helpers\DateHelper::toTr($patient->kayittarihi) ?>
    </td>
    <td class="pe-3 text-end">
        <span class="small fw-bold <?= empty($patient->sonizlemtarihi) ? 'text-danger' : 'text-success' ?>">
            <?= !empty($patient->sonizlemtarihi) ? \App\Helpers\DateHelper::toTr($patient->sonizlemtarihi) : 'İzlem Yok' ?>
        </span>
    </td>
</tr>
