<?php
/** @var object $patient */
$isMale = (($patient->cinsiyet ?? '') === '1' || ($patient->cinsiyet ?? '') === 'E');
$genderClass = $isMale ? 'esh-patient-gender-male' : 'esh-patient-gender-female';
?>
<tr class="esh-patient-row-hover">
    <td>
        <div class="dropdown">
            <a class="dropdown-toggle text-decoration-none esh-patient-name-link <?= $genderClass ?>" href="#" data-bs-toggle="dropdown">
                <?= htmlspecialchars($patient->isim . ' ' . $patient->soyisim) ?>
            </a>
            <ul class="dropdown-menu shadow border-0">
                <li><h6 class="dropdown-header">Hasta İşlemleri</h6></li>
                <li><a class="dropdown-item" href="<?= $viewlink; ?><?= $patient->id ?>"><i class="fa-solid fa-id-card text-primary me-2"></i> Bilgileri Göster</a></li>
                <li><a class="dropdown-item" href="<?= $editlink; ?><?= $patient->id ?>"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Hastayı Kaydet</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= $deletelink; ?><?= $patient->id ?>"><i class="fa-solid fa-trash-can text-info me-2"></i> Hastayı Sil</a></li>
            </ul>
        </div>
    </td>
    <td><code class="esh-tc-code fw-bold"><?= \App\Helpers\ValidationHelper::formatTc($patient->tckimlik) ?></code></td>
    <td>
        <div class="d-flex flex-column esh-patient-address-col">
            <div class="d-flex align-items-center mb-1">
                <i class="fa-solid fa-map-location-dot me-1 text-muted opacity-50"></i>
                <span class="small fw-semibold text-dark me-2"><?= $patient->mahalle_adi ?></span>
                <a href="<?= htmlspecialchars(esh_url('Patient', 'listwaiting', ['search' => urlencode($patient->ilce_adi)]), ENT_QUOTES, 'UTF-8') ?>"
                   class="badge bg-primary-subtle text-primary x-small fw-normal text-decoration-none">
                    <?= mb_strtoupper($patient->ilce_adi, 'UTF-8') ?>
                </a>
            </div>
            <div class="x-small text-muted"><?= $patient->sokak_adi ?> No: <?= $patient->kapino ?></div>
        </div>
    </td>
    <td class="x-small esh-contact-muted">
        <strong>A:</strong> <?= htmlspecialchars($patient->anneAdi) ?><br>
        <strong>B:</strong> <?= htmlspecialchars($patient->babaAdi) ?>
    </td>
    <td>
        <span class="small text-dark"><?= \App\Helpers\DateHelper::toTr($patient->dogumtarihi) ?></span><br>
        <span class="badge bg-light text-secondary border x-small"><?= \App\Helpers\DateHelper::calculateAge($patient->dogumtarihi) ?> Yaş</span>
    </td>
    <td class="x-small">
        <?php if (!empty($patient->ceptel1)): ?>
            <div class="small">
                <i class="fa-solid fa-mobile-screen text-success me-1"></i>
                <a href="tel:<?= $patient->ceptel1 ?>" class="text-decoration-none text-dark"><?= $patient->ceptel1 ?></a>
            </div>
        <?php endif; ?>
        <?php if (!empty($patient->ceptel2)): ?>
            <div class="x-small text-muted">
                <i class="fa-solid fa-phone me-1"></i>
                <a href="tel:<?= $patient->ceptel2 ?>" class="text-decoration-none text-muted"><?= $patient->ceptel2 ?></a>
            </div>
        <?php endif; ?>
    </td>
    <td class="x-small esh-contact-muted"><?= \App\Helpers\DateHelper::toTr($patient->kayittarihi) ?></td>
    <td class="small fw-bold <?= empty($patient->randevutarihi) ? 'text-danger' : 'text-success' ?>">
        <?= !empty($patient->randevutarihi) ? \App\Helpers\DateHelper::toTr($patient->randevutarihi) : 'Randevusu Yok' ?>
    </td>
</tr>
