<?php
/** @var object $patient */
?>
<tr>
    <td class="text-center p-1">
        <?php include __DIR__ . '/list_izlem_buttons.php'; ?>
    </td>
    <td>
        <div class="dropdown">
            <a class="dropdown-toggle text-decoration-none small esh-patient-name-link <?= (($patient->cinsiyet ?? '') === '1' || ($patient->cinsiyet ?? '') === 'E') ? 'esh-patient-gender-male' : 'esh-patient-gender-female' ?>" href="#" data-bs-toggle="dropdown">
                <?= htmlspecialchars($patient->isim . ' ' . $patient->soyisim) ?>
            </a>
            <div class="mt-1">
                <?= \App\Helpers\BadgeHelper::patientFeatures($patient) ?>
            </div>
            <ul class="dropdown-menu shadow border-0">
                <li><h6 class="dropdown-header">Hasta İşlemleri</h6></li>
                <li><a class="dropdown-item" href="<?= $viewlink; ?><?= $patient->id ?>"><i class="fa-solid fa-id-card text-primary me-2"></i> Bilgileri Göster</a></li>
                <li><a class="dropdown-item" href="<?= $editlink; ?><?= $patient->id ?>"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Bilgileri Düzenle</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => $patient->tckimlik]), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-list-check text-info me-2"></i> İzlem Geçmişi</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= $deletelink; ?><?= $patient->id ?>"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Hastayı Aktif Et</a></li>
            </ul>
        </div>
    </td>
    <td>
        <code class="esh-tc-code"><?= \App\Helpers\ValidationHelper::formatTc($patient->tckimlik) ?></code>
    </td>
    <td>
        <div class="d-flex flex-column">
            <div class="d-flex align-items-center mb-1">
                <i class="fa-solid fa-map-location-dot me-1 small text-muted opacity-50"></i>
                <span class="small fw-semibold text-dark me-2"><?= $patient->mahalle_adi ?></span>
            </div>
            <div class="x-small text-muted">
                <?= $patient->sokak_adi ?> No: <?= $patient->kapino ?>
                <a href="<?= htmlspecialchars(esh_url('Patient', 'listpassive', ['search' => urlencode($patient->ilce_adi)]), ENT_QUOTES, 'UTF-8') ?>"
                   class="badge bg-primary-soft text-primary x-small fw-normal text-decoration-none">
                    <?= mb_strtoupper($patient->ilce_adi, 'UTF-8') ?>
                </a>
            </div>
        </div>
    </td>
    <td>
        <div class="x-small text-muted">
            <strong>A:</strong> <?= htmlspecialchars($patient->anneAdi) ?><br>
            <strong>B:</strong> <?= htmlspecialchars($patient->babaAdi) ?>
        </div>
    </td>
    <td>
        <span class="small text-dark"><?= \App\Helpers\DateHelper::toTr($patient->dogumtarihi) ?></span><br>
        <span class="badge bg-light text-secondary border x-small">
            <?= \App\Helpers\DateHelper::calculateAge($patient->dogumtarihi) ?> Yaş
        </span>
    </td>
    <td class="x-small text-muted">
        <?= \App\Helpers\DateHelper::toTr($patient->kayittarihi) ?>
    </td>
    <td class="x-small text-muted <?= empty($patient->sonizlemtarihi) ? 'text-danger' : 'text-success' ?>">
        <?= !empty($patient->sonizlemtarihi) ? \App\Helpers\DateHelper::toTr($patient->sonizlemtarihi) : 'İzlem Yok' ?>
    </td>
    <td class="x-small text-muted <?= empty($patient->pasiftarihi) ? 'text-danger' : 'text-success' ?>">
        <?= !empty($patient->pasiftarihi) ? \App\Helpers\DateHelper::toTr($patient->pasiftarihi) : 'Tarih Yok' ?>
    </td>
    <td class="x-small <?= empty($patient->pasifnedeni) ? 'text-danger' : 'text-success' ?>">
        <?= !empty($patient->pasifnedeni) ? $pasifListesi[$patient->pasifnedeni] : 'Neden Yok' ?>
    </td>
</tr>
