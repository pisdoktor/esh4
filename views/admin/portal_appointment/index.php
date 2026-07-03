<?php
declare(strict_types=1);

use App\Helpers\CsrfHelper;
use App\Helpers\FormHelper;
use App\Helpers\PatientPortalHelper;

/** @var list<object> $rows */
$rows = $rows ?? [];
$tableReady = !empty($tableReady);
$queuedCount = (int) ($queuedCount ?? 0);
$durum = (string) ($durum ?? 'queued');

$durumOptions = [
    FormHelper::makeOption('queued', 'Bekleyen'),
    FormHelper::makeOption('approved', 'Onaylandı'),
    FormHelper::makeOption('rejected', 'Reddedildi'),
    FormHelper::makeOption('cancelled', 'İptal'),
    FormHelper::makeOption('all', 'Tümü'),
];

$zamanLabels = [0 => 'Sabah', 1 => 'Öğle', 2 => 'Akşam'];
?>
<div class="esh-page container-fluid py-4">
    <header class="mb-4 d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1 class="h4 mb-1"><i class="fa-solid fa-calendar-check me-2"></i>Portal randevu talepleri</h1>
            <p class="small text-muted mb-0">Hasta portalından gelen UHDS randevu değişiklik talepleri.</p>
        </div>
        <?php if ($queuedCount > 0): ?>
            <span class="badge bg-warning text-dark"><?= (int) $queuedCount ?> bekleyen</span>
        <?php endif; ?>
    </header>

    <?php if (!$tableReady): ?>
        <div class="alert alert-warning">
            Tablo kurulu değil. <code>database/migrate_esh_portal_appointment_requests.sql</code> dosyasını çalıştırın.
        </div>
    <?php else: ?>
        <form method="get" class="row g-2 align-items-end mb-3">
            <div class="col-auto">
                <?= FormHelper::fieldSelect('durum', 'Durum', $durumOptions, $durum, [
                    'col' => '',
                    'labelClass' => 'form-label small fw-semibold',
                    'class' => 'form-select-sm',
                    'tomSelect' => false,
                ]) ?>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary">Filtrele</button>
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Hasta</th>
                            <th>Mevcut</th>
                            <th>Talep</th>
                            <th>Neden</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($rows === []): ?>
                        <tr><td colspan="7" class="text-muted small py-4 text-center">Kayıt yok.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $z = isset($row->talep_zaman) ? (int) $row->talep_zaman : null;
                            $zLabel = ($z !== null && isset($zamanLabels[$z])) ? ' / ' . $zamanLabels[$z] : '';
                            ?>
                            <tr>
                                <td><?= (int) ($row->id ?? 0) ?></td>
                                <td>
                                    <?= htmlspecialchars(trim((string) ($row->isim ?? '') . ' ' . (string) ($row->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    <div class="small text-muted"><?= htmlspecialchars((string) ($row->tckimlik ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="small"><?= htmlspecialchars((string) ($row->mevcut_tarih ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?= htmlspecialchars((string) ($row->talep_tarih ?? ''), ENT_QUOTES, 'UTF-8') . $zLabel ?></td>
                                <td class="small"><?= htmlspecialchars((string) ($row->neden ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars((string) ($row->durum ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <?php if ((string) ($row->durum ?? '') === 'queued'): ?>
                                    <form method="post" action="<?= htmlspecialchars(esh_url('PortalAppointment', 'updateStatus'), ENT_QUOTES, 'UTF-8') ?>" class="d-flex flex-wrap gap-1">
                                        <?= CsrfHelper::hiddenField() ?>
                                        <input type="hidden" name="id" value="<?= (int) ($row->id ?? 0) ?>">
                                        <input type="hidden" name="durum" value="approved">
                                        <button type="submit" class="btn btn-success btn-sm">Onayla</button>
                                    </form>
                                    <form method="post" action="<?= htmlspecialchars(esh_url('PortalAppointment', 'updateStatus'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline mt-1">
                                        <?= CsrfHelper::hiddenField() ?>
                                        <input type="hidden" name="id" value="<?= (int) ($row->id ?? 0) ?>">
                                        <input type="hidden" name="durum" value="rejected">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Reddet</button>
                                    </form>
                                    <?php else: ?>
                                        <span class="small text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
