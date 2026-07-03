<?php
/**
 * @var array $metric
 * @var string $metricKey
 * @var int $summaryCount
 * @var int $total
 * @var int $page
 * @var int $limit
 * @var int $totalPages
 * @var string $pagelink
 * @var list<object> $rows
 * @var string $statsHubUrl
 * @var string $profileUrl
 * @var bool $statsAdminView
 * @var string $statsSubjectName
 */
use App\Helpers\DateHelper;
use App\Helpers\PaginationHelper;
use App\Helpers\ValidationHelper;

$listType = (string) ($metric['list_type'] ?? '');
$label = (string) ($metric['label'] ?? '');
$hint = (string) ($metric['hint'] ?? '');
$statsAdminView = !empty($statsAdminView);
$statsSubjectName = trim((string) ($statsSubjectName ?? ''));

$fmtDate = static function ($v, bool $withTime = false): string {
    $s = $withTime
        ? DateHelper::toTrDotDateTimeOrEmpty($v)
        : DateHelper::toTrDotOrEmpty($v);

    return $s !== '' ? $s : '—';
};

$patientName = static function ($row): string {
    $isim = trim((string) ($row->isim ?? ''));
    $soy = trim((string) ($row->soyisim ?? ''));

    return trim($isim . ' ' . $soy) !== '' ? trim($isim . ' ' . $soy) : '—';
};
?>
<div class="container py-4 esh-page-user-stats-detail au-page-stats-detail">
    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb mb-0">
            <?php if ($statsAdminView): ?>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>">Kullanıcılar</a></li>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($statsHubUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statsSubjectName !== '' ? $statsSubjectName : 'Personel', ENT_QUOTES, 'UTF-8') ?></a></li>
            <?php else: ?>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>">Profilim</a></li>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($statsHubUrl, ENT_QUOTES, 'UTF-8') ?>">İş özeti istatistikleri</a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-1"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></h4>
            <?php if ($hint !== ''): ?>
                <p class="text-muted small mb-0"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
        <a href="<?= htmlspecialchars($statsHubUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i>Tüm metrikler
        </a>
    </div>

    <div class="alert alert-light border small mb-4">
        Özet sayısı: <strong><?= number_format($summaryCount, 0, ',', '.') ?></strong>
        · Listelenen: <strong><?= number_format($total, 0, ',', '.') ?></strong> kayıt
    </div>

    <div class="au-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <?php if ($listType === 'visit'): ?>
                            <th class="ps-3">Tarih</th>
                            <th>Hasta</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                            <th class="pe-3 text-end">Bağlantı</th>
                        <?php elseif ($listType === 'plan'): ?>
                            <th class="ps-3">Plan tarihi</th>
                            <th>Hasta</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                            <th class="pe-3 text-end">Bağlantı</th>
                        <?php elseif ($listType === 'patient_visit' || $listType === 'patient_plan'): ?>
                            <th class="ps-3">Hasta</th>
                            <th>TC</th>
                            <th class="text-end">Kayıt</th>
                            <th class="pe-3">Son tarih</th>
                            <th class="pe-3 text-end">Bağlantı</th>
                        <?php elseif ($listType === 'nobet'): ?>
                            <th class="ps-3">Tarih</th>
                            <th>Tip</th>
                            <th class="text-end pe-3">Durum</th>
                        <?php elseif ($listType === 'izin' || $listType === 'istek'): ?>
                            <th class="ps-3">Başlangıç</th>
                            <th>Bitiş</th>
                            <th class="pe-3"><?= $listType === 'izin' ? 'Sebep' : 'Açıklama' ?></th>
                        <?php elseif ($listType === 'ekip'): ?>
                            <th class="ps-3">Tarih</th>
                            <th>Vardiya</th>
                            <th>Ekip no</th>
                            <th class="pe-3">Başlangıç</th>
                        <?php elseif ($listType === 'wound_photo'): ?>
                            <th class="ps-3">Çekim</th>
                            <th>Hasta</th>
                            <th>Bölge</th>
                            <th class="pe-3 text-end">Bağlantı</th>
                        <?php else: ?>
                            <th class="ps-3">Kayıt</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="6" class="text-muted small ps-3 py-4">Bu metrik için kayıt bulunamadı.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php if ($listType === 'visit'): ?>
                                <?php
                                $hid = (int) ($row->hid ?? 0);
                                $vid = (int) ($row->id ?? 0);
                                $done = (int) ($row->yapildimi ?? 0) === 1;
                                ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= $fmtDate($row->izlemtarihi ?? null) ?></td>
                                    <td><?= htmlspecialchars($patientName($row), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= $done ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                                            <?= $done ? 'Yapıldı' : 'Yapılmadı' ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= htmlspecialchars((string) ($row->yapilanlar ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <?php if ($vid > 0): ?>
                                            <a href="<?= htmlspecialchars(esh_url('Visit', 'edit', ["id" => $vid]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-success">İzlem</a>
                                        <?php endif; ?>
                                        <?php if ($hid > 0): ?>
                                            <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ["id" => $hid]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-primary">Hasta</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php elseif ($listType === 'plan'): ?>
                                <?php
                                $hid = (int) ($row->hid ?? 0);
                                $tc = (string) ($row->hastatckimlik ?? '');
                                $open = (int) ($row->durum ?? 0) === 0;
                                ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= $fmtDate($row->planlanantarih ?? null) ?></td>
                                    <td><?= htmlspecialchars($patientName($row), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= $open ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' ?>">
                                            <?= $open ? 'Bekleyen' : 'Tamam' ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= htmlspecialchars((string) ($row->yapilacaklar ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <?php if ($tc !== ''): ?>
                                            <a href="<?= htmlspecialchars(esh_url('PlannedVisit', 'patient', ['tc' => rawurlencode($tc)]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-primary">Planlar</a>
                                        <?php endif; ?>
                                        <?php if ($hid > 0): ?>
                                            <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ["id" => $hid]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-secondary">Hasta</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php elseif ($listType === 'patient_visit' || $listType === 'patient_plan'): ?>
                                <?php $hid = (int) ($row->hid ?? 0); ?>
                                <tr>
                                    <td class="ps-3"><?= htmlspecialchars($patientName($row), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="small font-monospace"><?= htmlspecialchars(ValidationHelper::formatTc((string) ($row->tckimlik ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end fw-semibold"><?= (int) ($row->kayit_sayisi ?? 0) ?></td>
                                    <td class="text-nowrap"><?= $fmtDate($listType === 'patient_visit' ? ($row->son_izlem_tarihi ?? null) : ($row->son_plan_tarihi ?? null)) ?></td>
                                    <td class="text-end pe-3">
                                        <?php if ($hid > 0): ?>
                                            <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ["id" => $hid]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-primary">Hasta</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php elseif ($listType === 'nobet'): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= $fmtDate($row->nobet_tarihi ?? null) ?></td>
                                    <td><?= htmlspecialchars((string) ($row->nobet_tipi ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end pe-3">
                                        <span class="badge bg-success-subtle text-success">Onaylı</span>
                                    </td>
                                </tr>
                            <?php elseif ($listType === 'izin'): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= $fmtDate($row->baslangic_tarihi ?? null) ?></td>
                                    <td class="text-nowrap"><?= $fmtDate($row->bitis_tarihi ?? null) ?></td>
                                    <td class="pe-3 small"><?= htmlspecialchars((string) ($row->sebep ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php elseif ($listType === 'istek'): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= $fmtDate($row->baslangic_tarihi ?? null) ?></td>
                                    <td class="text-nowrap"><?= $fmtDate($row->bitis_tarihi ?? null) ?></td>
                                    <td class="pe-3 small"><?= htmlspecialchars((string) ($row->aciklama ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php elseif ($listType === 'ekip'): ?>
                                <?php
                                $tarih = (string) ($row->tarih ?? '');
                                $isAdmin = \App\Helpers\AuthHelper::sessionIsAdmin();
                                ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= $fmtDate($tarih) ?></td>
                                    <td><?= htmlspecialchars((string) ($row->vardiya ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($row->ekip_no ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="pe-3">
                                        <?php if ($isAdmin && $tarih !== '' && \App\Helpers\AppSettings::isModuleEnabled('ekip')): ?>
                                            <a href="<?= htmlspecialchars(esh_url('Ekip', 'edit', ['tarih' => rawurlencode($tarih)]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-primary">Ekip günü</a>
                                        <?php else: ?>
                                            <?= htmlspecialchars((string) ($row->baslangic_saati ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php elseif ($listType === 'wound_photo'): ?>
                                <?php $hid = (int) ($row->hid ?? 0); ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= $fmtDate($row->cekim_tarihi ?? $row->created_at ?? null, true) ?></td>
                                    <td><?= htmlspecialchars($patientName($row), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="small"><?= htmlspecialchars((string) ($row->yara_bolgesi ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end pe-3">
                                        <?php if ($hid > 0): ?>
                                            <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ["id" => $hid]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-primary">Hasta</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total > 0): ?>
            <div class="au-panel__foot">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="small text-muted"><?= PaginationHelper::infoText($total, $page, $limit) ?></div>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <?= PaginationHelper::limitSelector($limit, $pagelink) ?>
                        <?= PaginationHelper::render($total, $page, $limit, $pagelink) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
